<?php

/**
 * Background Job Queue System for SuiteCRM
 * Handles asynchronous processing of tasks using Redis as the backend
 */
class JobQueue
{
    private $redis;
    private $queuePrefix = 'job_queue:';
    private $processingPrefix = 'processing:';
    private $failedPrefix = 'failed:';
    private $statsPrefix = 'stats:';
    
    // Queue priorities
    const PRIORITY_HIGH = 'high';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_LOW = 'low';
    
    // Job statuses
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRYING = 'retrying';

    public function __construct()
    {
        $cacheInstance = SugarCache::instance();
        if ($cacheInstance instanceof SugarCacheRedis) {
            $this->redis = $cacheInstance->getConnection();
        } else {
            throw new Exception('Redis cache not available for job queue');
        }
    }

    /**
     * Add job to queue
     */
    public function enqueue(string $jobType, array $payload, string $priority = self::PRIORITY_NORMAL, int $delay = 0): string
    {
        $jobId = $this->generateJobId();
        $job = [
            'id' => $jobId,
            'type' => $jobType,
            'payload' => $payload,
            'priority' => $priority,
            'created_at' => time(),
            'attempts' => 0,
            'max_attempts' => $this->getMaxAttempts($jobType),
            'delay_until' => $delay > 0 ? time() + $delay : null
        ];

        // Store job data
        $this->redis->hset($this->getJobKey($jobId), $job);
        
        // Add to appropriate queue
        if ($delay > 0) {
            // Delayed job - add to delayed queue with score as execution time
            $this->redis->zadd($this->queuePrefix . 'delayed', time() + $delay, $jobId);
        } else {
            // Immediate job - add to priority queue
            $this->redis->lpush($this->queuePrefix . $priority, $jobId);
        }

        // Update statistics
        $this->incrementStat('jobs_enqueued');
        $this->incrementStat("jobs_enqueued_{$jobType}");

        $GLOBALS['log']->info("Job enqueued: {$jobType} [{$jobId}]");
        
        return $jobId;
    }

    /**
     * Get next job from queue
     */
    public function dequeue(): ?array
    {
        // First, move any ready delayed jobs to regular queues
        $this->processDelayedJobs();
        
        // Try queues in priority order
        $queues = [
            self::PRIORITY_HIGH,
            self::PRIORITY_NORMAL,
            self::PRIORITY_LOW
        ];

        foreach ($queues as $priority) {
            $jobId = $this->redis->rpop($this->queuePrefix . $priority);
            if ($jobId) {
                $job = $this->redis->hgetall($this->getJobKey($jobId));
                if ($job) {
                    // Mark as processing
                    $job['status'] = self::STATUS_PROCESSING;
                    $job['started_at'] = time();
                    $job['attempts']++;
                    
                    // Store processing job
                    $this->redis->hset($this->getJobKey($jobId), $job);
                    $this->redis->setex($this->processingPrefix . $jobId, 3600, json_encode($job));
                    
                    // Update statistics
                    $this->incrementStat('jobs_processed');
                    
                    return $job;
                }
            }
        }

        return null;
    }

    /**
     * Mark job as completed
     */
    public function complete(string $jobId, array $result = []): bool
    {
        $job = $this->redis->hgetall($this->getJobKey($jobId));
        if (!$job) {
            return false;
        }

        $job['status'] = self::STATUS_COMPLETED;
        $job['completed_at'] = time();
        $job['result'] = json_encode($result);
        $job['duration'] = time() - ($job['started_at'] ?? time());

        // Update job record
        $this->redis->hset($this->getJobKey($jobId), $job);
        
        // Remove from processing
        $this->redis->del($this->processingPrefix . $jobId);
        
        // Update statistics
        $this->incrementStat('jobs_completed');
        $this->incrementStat("jobs_completed_{$job['type']}");
        
        // Clean up old completed jobs (keep for 24 hours)
        $this->redis->expire($this->getJobKey($jobId), 86400);

        $GLOBALS['log']->info("Job completed: {$job['type']} [{$jobId}] in {$job['duration']}s");
        
        return true;
    }

    /**
     * Mark job as failed
     */
    public function fail(string $jobId, string $error, bool $retry = true): bool
    {
        $job = $this->redis->hgetall($this->getJobKey($jobId));
        if (!$job) {
            return false;
        }

        $job['last_error'] = $error;
        $job['failed_at'] = time();
        
        // Check if should retry
        if ($retry && $job['attempts'] < $job['max_attempts']) {
            $job['status'] = self::STATUS_RETRYING;
            
            // Calculate backoff delay (exponential backoff)
            $delay = min(pow(2, $job['attempts']) * 60, 3600); // Max 1 hour
            $job['retry_at'] = time() + $delay;
            
            // Re-queue with delay
            $this->redis->zadd($this->queuePrefix . 'delayed', $job['retry_at'], $jobId);
            
            $GLOBALS['log']->warn("Job failed, retrying: {$job['type']} [{$jobId}] - {$error}");
        } else {
            $job['status'] = self::STATUS_FAILED;
            
            // Move to failed jobs
            $this->redis->lpush($this->failedPrefix . 'jobs', $jobId);
            
            // Update statistics
            $this->incrementStat('jobs_failed');
            $this->incrementStat("jobs_failed_{$job['type']}");
            
            $GLOBALS['log']->error("Job permanently failed: {$job['type']} [{$jobId}] - {$error}");
        }

        // Update job record
        $this->redis->hset($this->getJobKey($jobId), $job);
        
        // Remove from processing
        $this->redis->del($this->processingPrefix . $jobId);
        
        return true;
    }

    /**
     * Get job status
     */
    public function getJobStatus(string $jobId): ?array
    {
        $job = $this->redis->hgetall($this->getJobKey($jobId));
        return $job ?: null;
    }

    /**
     * Cancel job
     */
    public function cancel(string $jobId): bool
    {
        $job = $this->redis->hgetall($this->getJobKey($jobId));
        if (!$job) {
            return false;
        }

        // Remove from all queues
        $this->redis->lrem($this->queuePrefix . $job['priority'], 1, $jobId);
        $this->redis->zrem($this->queuePrefix . 'delayed', $jobId);
        $this->redis->del($this->processingPrefix . $jobId);
        
        // Mark as cancelled
        $job['status'] = 'cancelled';
        $job['cancelled_at'] = time();
        $this->redis->hset($this->getJobKey($jobId), $job);
        
        $this->incrementStat('jobs_cancelled');
        
        return true;
    }

    /**
     * Process delayed jobs that are ready
     */
    private function processDelayedJobs(): void
    {
        $now = time();
        $readyJobs = $this->redis->zrangebyscore($this->queuePrefix . 'delayed', '-inf', $now);
        
        foreach ($readyJobs as $jobId) {
            $job = $this->redis->hgetall($this->getJobKey($jobId));
            if ($job) {
                // Move to appropriate priority queue
                $this->redis->lpush($this->queuePrefix . $job['priority'], $jobId);
                $this->redis->zrem($this->queuePrefix . 'delayed', $jobId);
            }
        }
    }

    /**
     * Clean up stale processing jobs
     */
    public function cleanupStaleJobs(int $timeout = 3600): int
    {
        $staleJobIds = [];
        $processingKeys = $this->redis->keys($this->processingPrefix . '*');
        
        foreach ($processingKeys as $key) {
            $ttl = $this->redis->ttl($key);
            if ($ttl <= 0) {
                $jobId = str_replace($this->processingPrefix, '', $key);
                $staleJobIds[] = $jobId;
            }
        }
        
        $cleaned = 0;
        foreach ($staleJobIds as $jobId) {
            $job = $this->redis->hgetall($this->getJobKey($jobId));
            if ($job) {
                // Requeue the job
                $this->redis->lpush($this->queuePrefix . $job['priority'], $jobId);
                $this->redis->del($this->processingPrefix . $jobId);
                $cleaned++;
                
                $GLOBALS['log']->warn("Cleaned up stale job: {$job['type']} [{$jobId}]");
            }
        }
        
        return $cleaned;
    }

    /**
     * Get queue statistics
     */
    public function getStats(): array
    {
        $stats = [];
        
        // Queue sizes
        foreach ([self::PRIORITY_HIGH, self::PRIORITY_NORMAL, self::PRIORITY_LOW] as $priority) {
            $stats["queue_{$priority}"] = $this->redis->llen($this->queuePrefix . $priority);
        }
        
        $stats['delayed_jobs'] = $this->redis->zcard($this->queuePrefix . 'delayed');
        $stats['failed_jobs'] = $this->redis->llen($this->failedPrefix . 'jobs');
        $stats['processing_jobs'] = count($this->redis->keys($this->processingPrefix . '*'));
        
        // Global statistics
        $statKeys = $this->redis->keys($this->statsPrefix . '*');
        foreach ($statKeys as $key) {
            $statName = str_replace($this->statsPrefix, '', $key);
            $stats[$statName] = (int) $this->redis->get($key);
        }
        
        return $stats;
    }

    /**
     * Get failed jobs for analysis
     */
    public function getFailedJobs(int $limit = 100): array
    {
        $jobIds = $this->redis->lrange($this->failedPrefix . 'jobs', 0, $limit - 1);
        $jobs = [];
        
        foreach ($jobIds as $jobId) {
            $job = $this->redis->hgetall($this->getJobKey($jobId));
            if ($job) {
                $jobs[] = $job;
            }
        }
        
        return $jobs;
    }

    /**
     * Retry failed job
     */
    public function retryFailedJob(string $jobId): bool
    {
        $job = $this->redis->hgetall($this->getJobKey($jobId));
        if (!$job || $job['status'] !== self::STATUS_FAILED) {
            return false;
        }

        // Reset job for retry
        $job['status'] = self::STATUS_PENDING;
        $job['attempts'] = 0;
        unset($job['last_error'], $job['failed_at']);
        
        // Update job
        $this->redis->hset($this->getJobKey($jobId), $job);
        
        // Remove from failed queue and add to regular queue
        $this->redis->lrem($this->failedPrefix . 'jobs', 1, $jobId);
        $this->redis->lpush($this->queuePrefix . $job['priority'], $jobId);
        
        return true;
    }

    /**
     * Helper methods
     */
    
    private function generateJobId(): string
    {
        return uniqid('job_', true);
    }
    
    private function getJobKey(string $jobId): string
    {
        return "job:{$jobId}";
    }
    
    private function getMaxAttempts(string $jobType): int
    {
        $maxAttempts = [
            'email_notification' => 3,
            'pdf_generation' => 2,
            'inventory_sync' => 5,
            'data_export' => 2,
            'system_maintenance' => 1
        ];
        
        return $maxAttempts[$jobType] ?? 3;
    }
    
    private function incrementStat(string $statName): void
    {
        $this->redis->incr($this->statsPrefix . $statName);
    }
}

/**
 * Abstract base class for job processors
 */
abstract class JobProcessor
{
    protected $logger;
    
    public function __construct()
    {
        $this->logger = $GLOBALS['log'];
    }
    
    /**
     * Process the job
     */
    abstract public function process(array $job): array;
    
    /**
     * Check if processor can handle job type
     */
    abstract public function canHandle(string $jobType): bool;
    
    /**
     * Handle job execution with error handling
     */
    public function execute(array $job): array
    {
        $startTime = microtime(true);
        
        try {
            $this->logger->info("Processing job: {$job['type']} [{$job['id']}]");
            
            $result = $this->process($job);
            
            $duration = round(microtime(true) - $startTime, 3);
            $this->logger->info("Job processed successfully: {$job['type']} [{$job['id']}] in {$duration}s");
            
            return $result;
            
        } catch (Exception $e) {
            $duration = round(microtime(true) - $startTime, 3);
            $this->logger->error("Job processing failed: {$job['type']} [{$job['id']}] after {$duration}s - " . $e->getMessage());
            
            throw $e;
        }
    }
}
