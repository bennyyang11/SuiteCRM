<?php
/**
 * Job Interface for Background Jobs
 * All job classes must implement this interface
 */

interface JobInterface
{
    /**
     * Execute the job with given payload
     * 
     * @param array $payload Job data and parameters
     * @return array Job execution result
     * @throws Exception If job execution fails
     */
    public function execute($payload);
    
    /**
     * Get job metadata (optional)
     * 
     * @return array Job metadata including name, description, requirements
     */
    public function getMetadata();
}
