<?php

namespace Mostafaarafat\AiDatabase\Services;

use Illuminate\Support\Facades\DB;

class QueryService
{
    protected AiService $ai;
    protected SchemaService $schema;
    protected ?string $connection;
    protected bool $strictMode;

    public function __construct(
        AiService     $ai,
        SchemaService $schema,
        ?string       $connection = null
    )
    {
        $this->ai = $ai;
        $this->schema = $schema;
        $this->connection = $connection ?? config('ai-database.connection');
        $this->strictMode = config('ai-database.strict_mode', true);
    }

    /**
     * Generate SQL query from natural language
     */
    public function generateQuery(string $question): string
    {
        // Get relevant tables
        $tables = $this->schema->findRelevantTables($question, $this->ai);

        // Get schema for those tables
        $schemaText = $this->schema->getSchema($tables);

        // Build prompt
        $prompt = $this->buildPrompt($question, $schemaText);

        // Get SQL from AI
        $sql = $this->ai->complete($prompt);

        // Clean and validate
        $sql = $this->cleanSql($sql);
        $this->validateSql($sql);

        return $sql;
    }

    /**
     * Build the prompt for SQL generation
     */
    protected function buildPrompt(string $question, string $schema): string
    {
        return view('ai-database::prompts.query', [
            'schema' => $schema,
            'question' => $question,
            'strict_mode' => $this->strictMode,
        ])->render();
    }

    /**
     * Clean SQL response from AI
     */
    protected function cleanSql(string $sql): string
    {
        // Remove markdown code blocks
        $sql = preg_replace('/```sql\s*(.*?)\s*```/is', '$1', $sql);
        $sql = preg_replace('/```\s*(.*?)\s*```/is', '$1', $sql);

        // Remove common prefixes
        $sql = preg_replace('/^(SQL Query:|Query:|SQLQuery:)\s*/i', '', $sql);

        // Trim whitespace
        $sql = trim($sql);

        // Remove trailing semicolon
        $sql = rtrim($sql, ';');

        return $sql;
    }

    /**
     * Validate SQL query
     */
    protected function validateSql(string $sql): void
    {
        if (empty($sql)) {
            throw new \Exception('Generated SQL query is empty.');
        }

        if (!$this->strictMode) {
            return;
        }

        $sql = strtoupper(trim($sql));

        // Check for dangerous operations
        $dangerous = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE',
            'ALTER', 'CREATE', 'REPLACE', 'RENAME', 'GRANT',
            'REVOKE', 'EXEC', 'EXECUTE', 'CALL', 'MERGE'
        ];

        foreach ($dangerous as $keyword) {
            if (str_starts_with($sql, $keyword) || preg_match('/\b' . $keyword . '\b/', $sql)) {
                throw new \Exception(
                    "Unsafe SQL operation '{$keyword}' is not allowed in strict mode. " .
                    "Set AI_DATABASE_STRICT_MODE=false to allow write operations."
                );
            }
        }

        // Must be a SELECT query
        if (!str_starts_with($sql, 'SELECT')) {
            throw new \Exception('Only SELECT queries are allowed in strict mode.');
        }
    }

    /**
     * Execute a SQL query
     */
    public function execute(string $sql): array
    {
        $this->validateSql($sql);

        return DB::connection($this->connection)->select($sql);
    }
}