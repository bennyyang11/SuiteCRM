<?php
/**
 * SuiteCRM Manufacturing Security - Input Validation & Sanitization Framework
 * OWASP Compliant Input Validation with Comprehensive Sanitization
 */

if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

class InputValidator
{
    private static $validationRules = [];
    private static $sanitizationRules = [];
    private static $validationErrors = [];
    
    // Common validation patterns
    private static $patterns = [
        'email' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/',
        'phone' => '/^[\+]?[1-9][\d]{0,15}$/',
        'alphanumeric' => '/^[a-zA-Z0-9]+$/',
        'alpha' => '/^[a-zA-Z]+$/',
        'numeric' => '/^[0-9]+$/',
        'decimal' => '/^[0-9]+(\.[0-9]{1,2})?$/',
        'url' => '/^https?:\/\/[^\s\/$.?#].[^\s]*$/i',
        'uuid' => '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        'date' => '/^\d{4}-\d{2}-\d{2}$/',
        'datetime' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
        'slug' => '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        'username' => '/^[a-zA-Z0-9_]{3,20}$/',
        'password' => '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/'
    ];
    
    // Dangerous patterns to block
    private static $dangerousPatterns = [
        '/script\s*>/i',
        '/<\s*iframe/i',
        '/<\s*object/i',
        '/<\s*embed/i',
        '/<\s*form/i',
        '/javascript\s*:/i',
        '/vbscript\s*:/i',
        '/data\s*:/i',
        '/on\w+\s*=/i',
        '/expression\s*\(/i',
        '/union\s+select/i',
        '/drop\s+table/i',
        '/exec\s*\(/i',
        '/eval\s*\(/i',
        '/base64_decode/i',
        '/file_get_contents/i',
        '/system\s*\(/i',
        '/exec\s*\(/i',
        '/shell_exec/i',
        '/passthru/i'
    ];
    
    /**
     * Initialize validator with rules
     */
    public static function init($rules = [])
    {
        self::$validationRules = array_merge(self::getDefaultRules(), $rules);
        self::$sanitizationRules = self::getDefaultSanitizationRules();
        self::$validationErrors = [];
        
        SecurityAudit::logEvent('INPUT_VALIDATOR_INITIALIZED', 'Input validation system initialized', 'LOW', 'SYSTEM_ACCESS');
    }
    
    /**
     * Validate input data
     */
    public static function validate($data, $rules = null)
    {
        self::$validationErrors = [];
        
        if ($rules === null) {
            $rules = self::$validationRules;
        }
        
        foreach ($data as $field => $value) {
            if (isset($rules[$field])) {
                self::validateField($field, $value, $rules[$field]);
            }
        }
        
        if (!empty(self::$validationErrors)) {
            SecurityAudit::logEvent('INPUT_VALIDATION_FAILED', 
                'Input validation failed', 
                'MEDIUM', 
                'SECURITY_VIOLATION', 
                ['errors' => self::$validationErrors, 'data_fields' => array_keys($data)]
            );
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitize($data, $context = 'general')
    {
        if (is_array($data)) {
            return array_map(function($item) use ($context) {
                return self::sanitize($item, $context);
            }, $data);
        }
        
        if (!is_string($data)) {
            return $data;
        }
        
        // Check for dangerous patterns
        if (self::hasDangerousContent($data)) {
            SecurityAudit::logSecurityViolation('MALICIOUS_INPUT_DETECTED', 
                'Dangerous content detected in input', 
                ['input_preview' => substr($data, 0, 100), 'context' => $context]
            );
            return '';
        }
        
        // Apply context-specific sanitization
        switch ($context) {
            case 'html':
                return self::sanitizeHTML($data);
            case 'sql':
                return self::sanitizeSQL($data);
            case 'url':
                return self::sanitizeURL($data);
            case 'filename':
                return self::sanitizeFilename($data);
            case 'email':
                return self::sanitizeEmail($data);
            case 'phone':
                return self::sanitizePhone($data);
            case 'numeric':
                return self::sanitizeNumeric($data);
            case 'alphanumeric':
                return self::sanitizeAlphanumeric($data);
            default:
                return self::sanitizeGeneral($data);
        }
    }
    
    /**
     * Validate individual field
     */
    private static function validateField($field, $value, $rules)
    {
        foreach ($rules as $rule => $parameter) {
            switch ($rule) {
                case 'required':
                    if ($parameter && (empty($value) && $value !== '0')) {
                        self::$validationErrors[$field][] = "Field {$field} is required";
                    }
                    break;
                    
                case 'type':
                    if (!self::validateType($value, $parameter)) {
                        self::$validationErrors[$field][] = "Field {$field} must be of type {$parameter}";
                    }
                    break;
                    
                case 'pattern':
                    if (!self::validatePattern($value, $parameter)) {
                        self::$validationErrors[$field][] = "Field {$field} format is invalid";
                    }
                    break;
                    
                case 'min_length':
                    if (strlen($value) < $parameter) {
                        self::$validationErrors[$field][] = "Field {$field} must be at least {$parameter} characters";
                    }
                    break;
                    
                case 'max_length':
                    if (strlen($value) > $parameter) {
                        self::$validationErrors[$field][] = "Field {$field} must not exceed {$parameter} characters";
                    }
                    break;
                    
                case 'min_value':
                    if (is_numeric($value) && $value < $parameter) {
                        self::$validationErrors[$field][] = "Field {$field} must be at least {$parameter}";
                    }
                    break;
                    
                case 'max_value':
                    if (is_numeric($value) && $value > $parameter) {
                        self::$validationErrors[$field][] = "Field {$field} must not exceed {$parameter}";
                    }
                    break;
                    
                case 'in':
                    if (!in_array($value, $parameter)) {
                        self::$validationErrors[$field][] = "Field {$field} value is not allowed";
                    }
                    break;
                    
                case 'not_in':
                    if (in_array($value, $parameter)) {
                        self::$validationErrors[$field][] = "Field {$field} value is not allowed";
                    }
                    break;
                    
                case 'unique':
                    if (!self::validateUnique($field, $value, $parameter)) {
                        self::$validationErrors[$field][] = "Field {$field} value must be unique";
                    }
                    break;
                    
                case 'exists':
                    if (!self::validateExists($field, $value, $parameter)) {
                        self::$validationErrors[$field][] = "Field {$field} value does not exist";
                    }
                    break;
                    
                case 'custom':
                    if (is_callable($parameter)) {
                        $result = call_user_func($parameter, $value);
                        if ($result !== true) {
                            self::$validationErrors[$field][] = is_string($result) ? $result : "Field {$field} validation failed";
                        }
                    }
                    break;
            }
        }
    }
    
    /**
     * Validate data type
     */
    private static function validateType($value, $type)
    {
        switch ($type) {
            case 'string':
                return is_string($value);
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            case 'float':
                return filter_var($value, FILTER_VALIDATE_FLOAT) !== false;
            case 'boolean':
                return is_bool($value) || in_array(strtolower($value), ['true', 'false', '1', '0']);
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($value, FILTER_VALIDATE_URL) !== false;
            case 'ip':
                return filter_var($value, FILTER_VALIDATE_IP) !== false;
            case 'date':
                return DateTime::createFromFormat('Y-m-d', $value) !== false;
            case 'datetime':
                return DateTime::createFromFormat('Y-m-d H:i:s', $value) !== false;
            default:
                return true;
        }
    }
    
    /**
     * Validate against pattern
     */
    private static function validatePattern($value, $pattern)
    {
        if (isset(self::$patterns[$pattern])) {
            return preg_match(self::$patterns[$pattern], $value);
        }
        
        if (is_string($pattern) && strpos($pattern, '/') === 0) {
            return preg_match($pattern, $value);
        }
        
        return true;
    }
    
    /**
     * Validate uniqueness in database
     */
    private static function validateUnique($field, $value, $config)
    {
        global $db;
        
        try {
            $table = $config['table'];
            $column = $config['column'] ?? $field;
            $except = $config['except'] ?? null;
            
            $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
            $params = [$value];
            $types = 's';
            
            if ($except) {
                $query .= " AND id != ?";
                $params[] = $except;
                $types .= 's';
            }
            
            $stmt = $db->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row['count'] == 0;
            
        } catch (Exception $e) {
            SecurityAudit::logEvent('VALIDATION_DATABASE_ERROR', $e->getMessage(), 'HIGH', 'SYSTEM_ACCESS');
            return true; // Allow if validation fails to prevent blocking legitimate users
        }
    }
    
    /**
     * Validate existence in database
     */
    private static function validateExists($field, $value, $config)
    {
        global $db;
        
        try {
            $table = $config['table'];
            $column = $config['column'] ?? $field;
            
            $query = "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?";
            $stmt = $db->prepare($query);
            $stmt->bind_param('s', $value);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            return $row['count'] > 0;
            
        } catch (Exception $e) {
            SecurityAudit::logEvent('VALIDATION_DATABASE_ERROR', $e->getMessage(), 'HIGH', 'SYSTEM_ACCESS');
            return true; // Allow if validation fails
        }
    }
    
    /**
     * Check for dangerous content
     */
    private static function hasDangerousContent($input)
    {
        foreach (self::$dangerousPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Sanitize HTML content
     */
    private static function sanitizeHTML($input)
    {
        // Remove dangerous tags and attributes
        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
        $input = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $input);
        $input = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);
        $input = preg_replace('/javascript\s*:/i', '', $input);
        
        // Use HTMLPurifier if available, otherwise basic filtering
        if (class_exists('HTMLPurifier')) {
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);
            return $purifier->purify($input);
        }
        
        return strip_tags($input, '<p><br><strong><em><u><ol><ul><li><a><img>');
    }
    
    /**
     * Sanitize for SQL context
     */
    private static function sanitizeSQL($input)
    {
        // Remove SQL injection patterns
        $input = preg_replace('/union\s+select/i', '', $input);
        $input = preg_replace('/drop\s+table/i', '', $input);
        $input = preg_replace('/exec\s*\(/i', '', $input);
        
        return addslashes($input);
    }
    
    /**
     * Sanitize URL
     */
    private static function sanitizeURL($input)
    {
        // Remove dangerous protocols
        $input = preg_replace('/^(javascript|data|vbscript):/i', '', $input);
        
        return filter_var($input, FILTER_SANITIZE_URL);
    }
    
    /**
     * Sanitize filename
     */
    private static function sanitizeFilename($input)
    {
        // Remove directory traversal
        $input = str_replace(['../', '..\\', '\\', '/'], '', $input);
        
        // Remove dangerous characters
        $input = preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
        
        return substr($input, 0, 255);
    }
    
    /**
     * Sanitize email
     */
    private static function sanitizeEmail($input)
    {
        return filter_var($input, FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize phone number
     */
    private static function sanitizePhone($input)
    {
        return preg_replace('/[^0-9\+\-\(\)\s]/', '', $input);
    }
    
    /**
     * Sanitize numeric input
     */
    private static function sanitizeNumeric($input)
    {
        return preg_replace('/[^0-9.]/', '', $input);
    }
    
    /**
     * Sanitize alphanumeric input
     */
    private static function sanitizeAlphanumeric($input)
    {
        return preg_replace('/[^a-zA-Z0-9]/', '', $input);
    }
    
    /**
     * General sanitization
     */
    private static function sanitizeGeneral($input)
    {
        // Remove null bytes
        $input = str_replace("\0", '', $input);
        
        // Remove control characters except newline and tab
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
        
        // Basic HTML entity encoding
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Get validation errors
     */
    public static function getErrors()
    {
        return self::$validationErrors;
    }
    
    /**
     * Get first validation error
     */
    public static function getFirstError()
    {
        if (empty(self::$validationErrors)) {
            return null;
        }
        
        $firstField = array_key_first(self::$validationErrors);
        return self::$validationErrors[$firstField][0] ?? null;
    }
    
    /**
     * Clear validation errors
     */
    public static function clearErrors()
    {
        self::$validationErrors = [];
    }
    
    /**
     * Get default validation rules
     */
    private static function getDefaultRules()
    {
        return [
            'email' => [
                'required' => true,
                'type' => 'email',
                'max_length' => 255
            ],
            'username' => [
                'required' => true,
                'pattern' => 'username',
                'min_length' => 3,
                'max_length' => 20
            ],
            'password' => [
                'required' => true,
                'pattern' => 'password',
                'min_length' => 12,
                'max_length' => 128
            ],
            'phone' => [
                'pattern' => 'phone',
                'max_length' => 20
            ],
            'url' => [
                'type' => 'url',
                'max_length' => 2048
            ],
            'amount' => [
                'type' => 'float',
                'min_value' => 0
            ],
            'quantity' => [
                'type' => 'integer',
                'min_value' => 0
            ]
        ];
    }
    
    /**
     * Get default sanitization rules
     */
    private static function getDefaultSanitizationRules()
    {
        return [
            'email' => 'email',
            'phone' => 'phone',
            'url' => 'url',
            'amount' => 'numeric',
            'quantity' => 'numeric',
            'description' => 'html',
            'comment' => 'html',
            'filename' => 'filename'
        ];
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $config = [])
    {
        $errors = [];
        
        // Default configuration
        $defaultConfig = [
            'max_size' => 5 * 1024 * 1024, // 5MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            'allowed_mime_types' => [
                'image/jpeg', 'image/png', 'image/gif', 
                'application/pdf', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ]
        ];
        
        $config = array_merge($defaultConfig, $config);
        
        // Check if file was uploaded
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload failed';
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $config['max_size']) {
            $errors[] = 'File size exceeds limit';
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $config['allowed_types'])) {
            $errors[] = 'File type not allowed';
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $config['allowed_mime_types'])) {
            $errors[] = 'Invalid file format';
        }
        
        // Check for malicious content
        $content = file_get_contents($file['tmp_name']);
        if (self::hasDangerousContent($content)) {
            $errors[] = 'File contains malicious content';
            SecurityAudit::logSecurityViolation('MALICIOUS_FILE_UPLOAD', 
                'Malicious file upload attempt', 
                ['filename' => $file['name'], 'size' => $file['size']]
            );
        }
        
        if (!empty($errors)) {
            SecurityAudit::logEvent('FILE_UPLOAD_VALIDATION_FAILED', 
                'File upload validation failed', 
                'MEDIUM', 
                'SECURITY_VIOLATION', 
                ['filename' => $file['name'], 'errors' => $errors]
            );
        }
        
        return $errors;
    }
    
    /**
     * Create API input validation middleware
     */
    public static function createAPIMiddleware($rules)
    {
        return function($request, $response, $next) use ($rules) {
            $input = $request->getParsedBody() ?: [];
            
            // Validate input
            if (!self::validate($input, $rules)) {
                return $response->withStatus(400)->withJson([
                    'error' => 'Validation failed',
                    'details' => self::getErrors()
                ]);
            }
            
            // Sanitize input
            $sanitizedInput = [];
            foreach ($input as $key => $value) {
                $context = self::$sanitizationRules[$key] ?? 'general';
                $sanitizedInput[$key] = self::sanitize($value, $context);
            }
            
            // Replace request body with sanitized data
            $request = $request->withParsedBody($sanitizedInput);
            
            return $next($request, $response);
        };
    }
}

/**
 * Manufacturing-specific input validation
 */
class ManufacturingValidator extends InputValidator
{
    /**
     * Validate product data
     */
    public static function validateProduct($data)
    {
        $rules = [
            'name' => [
                'required' => true,
                'max_length' => 255,
                'pattern' => '/^[a-zA-Z0-9\s\-_]+$/'
            ],
            'sku' => [
                'required' => true,
                'pattern' => '/^[A-Z0-9\-]{3,20}$/',
                'unique' => ['table' => 'products', 'column' => 'sku']
            ],
            'price' => [
                'required' => true,
                'type' => 'float',
                'min_value' => 0.01
            ],
            'category_id' => [
                'required' => true,
                'exists' => ['table' => 'product_categories', 'column' => 'id']
            ],
            'description' => [
                'max_length' => 2000
            ]
        ];
        
        return parent::validate($data, $rules);
    }
    
    /**
     * Validate order data
     */
    public static function validateOrder($data)
    {
        $rules = [
            'customer_id' => [
                'required' => true,
                'exists' => ['table' => 'accounts', 'column' => 'id']
            ],
            'items' => [
                'required' => true,
                'type' => 'array',
                'custom' => function($items) {
                    if (empty($items)) {
                        return 'Order must contain at least one item';
                    }
                    foreach ($items as $item) {
                        if (!isset($item['product_id']) || !isset($item['quantity'])) {
                            return 'Each item must have product_id and quantity';
                        }
                    }
                    return true;
                }
            ],
            'shipping_address' => [
                'required' => true,
                'max_length' => 500
            ]
        ];
        
        return parent::validate($data, $rules);
    }
    
    /**
     * Validate quote data
     */
    public static function validateQuote($data)
    {
        $rules = [
            'customer_id' => [
                'required' => true,
                'exists' => ['table' => 'accounts', 'column' => 'id']
            ],
            'valid_until' => [
                'required' => true,
                'type' => 'date',
                'custom' => function($date) {
                    $validUntil = DateTime::createFromFormat('Y-m-d', $date);
                    $today = new DateTime();
                    return $validUntil > $today ? true : 'Quote expiration date must be in the future';
                }
            ],
            'items' => [
                'required' => true,
                'type' => 'array'
            ]
        ];
        
        return parent::validate($data, $rules);
    }
}
