<?php

/**
 * Secure Query Builder - Prevents SQL Injection
 * Provides parameterized query building with automatic escaping
 */
class SecureQueryBuilder
{
    private $db;
    private $bindings = [];
    private $bindCounter = 0;
    
    // Allowed operators for WHERE clauses
    private $allowedOperators = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'IS NULL', 'IS NOT NULL'];
    
    // Allowed functions for SELECT clauses
    private $allowedFunctions = ['COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'CONCAT', 'UPPER', 'LOWER', 'TRIM', 'DATE', 'NOW'];

    public function __construct($database = null)
    {
        $this->db = $database ?? DBManagerFactory::getInstance();
    }

    /**
     * Create a SELECT query builder
     */
    public function select(array $columns = ['*']): SelectQuery
    {
        return new SelectQuery($this, $columns);
    }

    /**
     * Create an INSERT query builder
     */
    public function insert(string $table): InsertQuery
    {
        return new InsertQuery($this, $table);
    }

    /**
     * Create an UPDATE query builder
     */
    public function update(string $table): UpdateQuery
    {
        return new UpdateQuery($this, $table);
    }

    /**
     * Create a DELETE query builder
     */
    public function delete(string $table): DeleteQuery
    {
        return new DeleteQuery($this, $table);
    }

    /**
     * Execute a parameterized query
     */
    public function execute(string $sql, array $bindings = []): bool
    {
        $this->validateSql($sql);
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            
            foreach ($bindings as $key => $value) {
                $stmt->bindValue($key, $value, $this->getPdoType($value));
            }
            
            $result = $stmt->execute();
            
            // Log query for debugging (without sensitive data)
            $this->logQuery($sql, count($bindings));
            
            return $result;
            
        } catch (PDOException $e) {
            $GLOBALS['log']->error("Query execution failed: " . $e->getMessage());
            throw new DatabaseException("Query execution failed", 0, $e);
        }
    }

    /**
     * Execute a query and return results
     */
    public function query(string $sql, array $bindings = []): array
    {
        $this->validateSql($sql);
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            
            foreach ($bindings as $key => $value) {
                $stmt->bindValue($key, $value, $this->getPdoType($value));
            }
            
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Log query for debugging
            $this->logQuery($sql, count($bindings));
            
            return $result;
            
        } catch (PDOException $e) {
            $GLOBALS['log']->error("Query execution failed: " . $e->getMessage());
            throw new DatabaseException("Query execution failed", 0, $e);
        }
    }

    /**
     * Validate table name against allowed tables
     */
    public function validateTableName(string $tableName): bool
    {
        // Get list of valid SuiteCRM tables
        $validTables = $this->getValidTables();
        
        if (!in_array($tableName, $validTables)) {
            throw new SecurityException("Invalid table name: {$tableName}");
        }
        
        return true;
    }

    /**
     * Validate column name
     */
    public function validateColumnName(string $columnName): bool
    {
        // Allow alphanumeric, underscore, and dot for table.column
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $columnName)) {
            throw new SecurityException("Invalid column name: {$columnName}");
        }
        
        return true;
    }

    /**
     * Validate operator
     */
    public function validateOperator(string $operator): bool
    {
        $operator = strtoupper(trim($operator));
        
        if (!in_array($operator, $this->allowedOperators)) {
            throw new SecurityException("Invalid operator: {$operator}");
        }
        
        return true;
    }

    /**
     * Generate binding parameter name
     */
    public function generateBinding(string $prefix = 'param'): string
    {
        return ":{$prefix}" . ++$this->bindCounter;
    }

    /**
     * Escape identifier (table/column names)
     */
    public function escapeIdentifier(string $identifier): string
    {
        // Remove any existing backticks and add new ones
        $identifier = str_replace('`', '', $identifier);
        return "`{$identifier}`";
    }

    /**
     * Get PDO parameter type
     */
    private function getPdoType($value): int
    {
        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        } elseif (is_int($value)) {
            return PDO::PARAM_INT;
        } elseif (is_null($value)) {
            return PDO::PARAM_NULL;
        } else {
            return PDO::PARAM_STR;
        }
    }

    /**
     * Validate SQL for dangerous patterns
     */
    private function validateSql(string $sql): void
    {
        // Check for dangerous SQL patterns
        $dangerousPatterns = [
            '/\b(DROP|CREATE|ALTER|TRUNCATE|GRANT|REVOKE)\b/i',
            '/\b(LOAD_FILE|INTO OUTFILE|INTO DUMPFILE)\b/i',
            '/\b(UNION\s+SELECT)\b/i', // Basic union injection protection
            '/;\s*--/', // SQL comment injection
            '/\/\*.*\*\//', // SQL comment blocks
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                throw new SecurityException("Potentially dangerous SQL detected");
            }
        }
    }

    /**
     * Get list of valid SuiteCRM tables
     */
    private function getValidTables(): array
    {
        static $validTables = null;
        
        if ($validTables === null) {
            // Cache table list to avoid repeated queries
            $cacheKey = 'suitecrm_valid_tables';
            $cache = SugarCache::instance();
            $validTables = $cache->get($cacheKey);
            
            if (!$validTables) {
                try {
                    $result = $this->db->query("SHOW TABLES");
                    $validTables = [];
                    
                    while ($row = $this->db->fetchByAssoc($result)) {
                        $validTables[] = reset($row);
                    }
                    
                    // Cache for 1 hour
                    $cache->set($cacheKey, $validTables, 3600);
                    
                } catch (Exception $e) {
                    $GLOBALS['log']->error("Failed to get table list: " . $e->getMessage());
                    $validTables = [];
                }
            }
        }
        
        return $validTables;
    }

    /**
     * Log query execution for monitoring
     */
    private function logQuery(string $sql, int $paramCount): void
    {
        if ($GLOBALS['log']->getLevel() === 'debug') {
            $sanitizedSql = preg_replace('/\s+/', ' ', trim($sql));
            $GLOBALS['log']->debug("Executed parameterized query with {$paramCount} parameters: " . substr($sanitizedSql, 0, 200));
        }
    }
}

/**
 * Base query class
 */
abstract class BaseQuery
{
    protected $queryBuilder;
    protected $bindings = [];
    protected $bindCounter = 0;

    public function __construct(SecureQueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Add parameter binding
     */
    protected function addBinding($value): string
    {
        $param = $this->queryBuilder->generateBinding();
        $this->bindings[$param] = $value;
        return $param;
    }

    /**
     * Get bindings array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Build and execute the query
     */
    abstract public function execute();

    /**
     * Get the SQL string
     */
    abstract public function toSql(): string;
}

/**
 * SELECT query builder
 */
class SelectQuery extends BaseQuery
{
    private $columns = [];
    private $from = '';
    private $joins = [];
    private $wheres = [];
    private $orderBy = [];
    private $groupBy = [];
    private $having = [];
    private $limit = null;
    private $offset = null;

    public function __construct(SecureQueryBuilder $queryBuilder, array $columns)
    {
        parent::__construct($queryBuilder);
        $this->columns = $columns;
    }

    public function from(string $table): self
    {
        $this->queryBuilder->validateTableName($table);
        $this->from = $this->queryBuilder->escapeIdentifier($table);
        return $this;
    }

    public function join(string $table, string $column1, string $operator, string $column2, string $type = 'INNER'): self
    {
        $this->queryBuilder->validateTableName($table);
        $this->queryBuilder->validateColumnName($column1);
        $this->queryBuilder->validateColumnName($column2);
        $this->queryBuilder->validateOperator($operator);

        $this->joins[] = [
            'type' => strtoupper($type),
            'table' => $this->queryBuilder->escapeIdentifier($table),
            'condition' => "{$this->queryBuilder->escapeIdentifier($column1)} {$operator} {$this->queryBuilder->escapeIdentifier($column2)}"
        ];

        return $this;
    }

    public function where(string $column, string $operator, $value): self
    {
        $this->queryBuilder->validateColumnName($column);
        $this->queryBuilder->validateOperator($operator);

        $param = $this->addBinding($value);
        $this->wheres[] = "{$this->queryBuilder->escapeIdentifier($column)} {$operator} {$param}";

        return $this;
    }

    public function whereIn(string $column, array $values): self
    {
        $this->queryBuilder->validateColumnName($column);
        
        $params = [];
        foreach ($values as $value) {
            $params[] = $this->addBinding($value);
        }
        
        $this->wheres[] = "{$this->queryBuilder->escapeIdentifier($column)} IN (" . implode(', ', $params) . ")";
        
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->queryBuilder->validateColumnName($column);
        $direction = strtoupper($direction);
        
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new SecurityException("Invalid sort direction: {$direction}");
        }
        
        $this->orderBy[] = "{$this->queryBuilder->escapeIdentifier($column)} {$direction}";
        
        return $this;
    }

    public function limit(int $limit, int $offset = 0): self
    {
        $this->limit = max(1, $limit);
        $this->offset = max(0, $offset);
        return $this;
    }

    public function toSql(): string
    {
        if (empty($this->from)) {
            throw new InvalidArgumentException("FROM clause is required");
        }

        $sql = "SELECT " . implode(', ', $this->columns) . " FROM {$this->from}";

        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $sql .= " {$join['type']} JOIN {$join['table']} ON {$join['condition']}";
            }
        }

        if (!empty($this->wheres)) {
            $sql .= " WHERE " . implode(' AND ', $this->wheres);
        }

        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        if (!empty($this->having)) {
            $sql .= " HAVING " . implode(' AND ', $this->having);
        }

        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
            if ($this->offset > 0) {
                $sql .= " OFFSET {$this->offset}";
            }
        }

        return $sql;
    }

    public function execute(): array
    {
        return $this->queryBuilder->query($this->toSql(), $this->getBindings());
    }

    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->execute();
        return $results[0] ?? null;
    }
}

/**
 * Custom exceptions
 */
class SecurityException extends Exception {}
class DatabaseException extends Exception {}
