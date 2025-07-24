<?php
/**
 * Email Notification Background Job
 * Handles email sending with templates and retry logic
 */

require_once('include/BackgroundJobs/JobInterface.php');

class EmailNotificationJob implements JobInterface
{
    /**
     * Execute email notification
     */
    public function execute($payload)
    {
        $startTime = microtime(true);
        
        try {
            // Validate payload
            $this->validatePayload($payload);
            
            // Prepare email data
            $emailData = $this->prepareEmailData($payload);
            
            // Send email
            $result = $this->sendEmail($emailData);
            
            // Log email activity
            $this->logEmailActivity($emailData, $result);
            
            $executionTime = (microtime(true) - $startTime) * 1000;
            
            return [
                'success' => true,
                'message_id' => $result['message_id'] ?? null,
                'recipient' => $emailData['to'],
                'subject' => $emailData['subject'],
                'execution_time_ms' => round($executionTime, 2)
            ];
            
        } catch (Exception $e) {
            $GLOBALS['log']->error("Email notification job failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate email payload
     */
    protected function validatePayload($payload)
    {
        $required = ['to', 'subject'];
        
        foreach ($required as $field) {
            if (empty($payload[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Validate email address
        if (!filter_var($payload['to'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email address: {$payload['to']}");
        }
    }

    /**
     * Prepare email data
     */
    protected function prepareEmailData($payload)
    {
        $emailData = [
            'to' => $payload['to'],
            'subject' => $payload['subject'],
            'body' => $payload['body'] ?? '',
            'from' => $payload['from'] ?? $this->getDefaultFromAddress(),
            'reply_to' => $payload['reply_to'] ?? null,
            'cc' => $payload['cc'] ?? [],
            'bcc' => $payload['bcc'] ?? [],
            'attachments' => $payload['attachments'] ?? [],
            'priority' => $payload['priority'] ?? 'normal',
            'content_type' => $payload['content_type'] ?? 'text/html'
        ];
        
        // Process template if specified
        if (!empty($payload['template'])) {
            $emailData['body'] = $this->renderTemplate($payload['template'], $payload['data'] ?? []);
        }
        
        return $emailData;
    }

    /**
     * Send email using SuiteCRM's email system
     */
    protected function sendEmail($emailData)
    {
        require_once('modules/Emails/Email.php');
        require_once('include/SugarPHPMailer.php');
        
        $mailer = new SugarPHPMailer();
        
        // Configure mailer
        $this->configureMailer($mailer);
        
        // Set recipients
        $mailer->addAddress($emailData['to']);
        
        if (!empty($emailData['cc'])) {
            foreach ((array)$emailData['cc'] as $cc) {
                $mailer->addCC($cc);
            }
        }
        
        if (!empty($emailData['bcc'])) {
            foreach ((array)$emailData['bcc'] as $bcc) {
                $mailer->addBCC($bcc);
            }
        }
        
        // Set email content
        $mailer->setFrom($emailData['from']);
        $mailer->Subject = $emailData['subject'];
        $mailer->Body = $emailData['body'];
        $mailer->isHTML($emailData['content_type'] === 'text/html');
        
        // Set reply-to if specified
        if (!empty($emailData['reply_to'])) {
            $mailer->addReplyTo($emailData['reply_to']);
        }
        
        // Add attachments
        foreach ($emailData['attachments'] as $attachment) {
            if (is_array($attachment)) {
                $mailer->addAttachment($attachment['path'], $attachment['name'] ?? '');
            } else {
                $mailer->addAttachment($attachment);
            }
        }
        
        // Set priority
        switch ($emailData['priority']) {
            case 'high':
                $mailer->Priority = 1;
                break;
            case 'low':
                $mailer->Priority = 5;
                break;
            default:
                $mailer->Priority = 3;
        }
        
        // Send email
        if (!$mailer->send()) {
            throw new Exception("Failed to send email: " . $mailer->ErrorInfo);
        }
        
        return [
            'message_id' => $mailer->getLastMessageID(),
            'sent_at' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * Configure PHPMailer with system settings
     */
    protected function configureMailer($mailer)
    {
        global $sugar_config;
        
        $mailConfig = $sugar_config['mail'] ?? [];
        
        if (!empty($mailConfig['smtp_host'])) {
            $mailer->isSMTP();
            $mailer->Host = $mailConfig['smtp_host'];
            $mailer->Port = $mailConfig['smtp_port'] ?? 587;
            
            if (!empty($mailConfig['smtp_username'])) {
                $mailer->SMTPAuth = true;
                $mailer->Username = $mailConfig['smtp_username'];
                $mailer->Password = $mailConfig['smtp_password'] ?? '';
            }
            
            $mailer->SMTPSecure = $mailConfig['smtp_security'] ?? 'tls';
        }
        
        $mailer->CharSet = 'UTF-8';
        $mailer->Encoding = 'base64';
    }

    /**
     * Render email template
     */
    protected function renderTemplate($templateName, $data)
    {
        $templatePath = $this->getTemplatePath($templateName);
        
        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found: {$templateName}");
        }
        
        // Start output buffering
        ob_start();
        
        // Extract variables for template
        extract($data);
        
        // Include template
        include $templatePath;
        
        // Get rendered content
        $content = ob_get_clean();
        
        return $content;
    }

    /**
     * Get template file path
     */
    protected function getTemplatePath($templateName)
    {
        $paths = [
            "custom/modules/Emails/templates/{$templateName}.php",
            "modules/Emails/templates/{$templateName}.php",
            "include/BackgroundJobs/EmailTemplates/{$templateName}.php"
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        throw new Exception("Template file not found: {$templateName}");
    }

    /**
     * Get default from address
     */
    protected function getDefaultFromAddress()
    {
        global $sugar_config;
        
        return $sugar_config['mail']['default_from_address'] ?? 'noreply@suitecrm.com';
    }

    /**
     * Log email activity
     */
    protected function logEmailActivity($emailData, $result)
    {
        global $db;
        
        $sql = "
        INSERT INTO email_activity_log (
            recipient, subject, sent_at, message_id, status, created_at
        ) VALUES (?, ?, ?, ?, 'sent', NOW())
        ";
        
        $db->pQuery($sql, [
            $emailData['to'],
            $emailData['subject'],
            $result['sent_at'],
            $result['message_id'] ?? ''
        ]);
    }

    /**
     * Get job metadata
     */
    public function getMetadata()
    {
        return [
            'name' => 'Email Notification',
            'description' => 'Sends email notifications with template support',
            'estimated_duration' => '5-15 seconds',
            'memory_requirements' => '32MB',
            'dependencies' => ['smtp_server', 'email_templates']
        ];
    }
}
