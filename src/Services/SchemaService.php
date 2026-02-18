<?php

namespace Mostafaarafat\AiDatabase\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaService
{
    protected ?string $connection;

    public function __construct(?string $connection = null)
    {
        $this->connection = $connection ?? config('ai-database.connection');
    }

    /**
     * Get database schema as text
     */
    public function getSchema(array $tables = null): string
    {
        $cacheKey = $this->getCacheKey($tables);

        if (config('ai-database.cache.enabled')) {
            return Cache::remember($cacheKey, config('ai-database.cache.ttl'), function () use ($tables) {
                return $this->buildSchema($tables);
            });
        }

        return $this->buildSchema($tables);
    }

    protected function getCacheKey(?array $tables): string
    {
        $prefix = config('ai-database.cache.prefix');
        $connection = $this->connection ?? 'default';
        $tablesKey = $tables ? md5(implode(',', $tables)) : 'all';

        return "{$prefix}:schema:{$connection}:{$tablesKey}";
    }

    protected function buildSchema(?array $tables): string
    {
        $tables = $tables ?? $this->getAllTables();
        $schema = "Database Schema:\n\n";

        foreach ($tables as $table) {
            $schema .= $this->getTableSchema($table);
            $schema .= "\n";
        }

        return $schema;
    }

    /**
     * Get all table names
     */
    public function getAllTables(): array
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        return match ($driver) {
            'mysql' => $this->getMySQLTables($connection),
            'pgsql' => $this->getPostgreSQLTables($connection),
            'sqlite' => $this->getSQLiteTables($connection),
            default => $this->getGenericTables(),
        };
    }

    protected function getMySQLTables($connection): array
    {
        $tables = $connection->select('SHOW TABLES');
        return array_map(fn($table) => array_values((array)$table)[0], $tables);
    }

    protected function getPostgreSQLTables($connection): array
    {
        $tables = $connection->select(
            "SELECT tablename FROM pg_catalog.pg_tables WHERE schemaname = 'public'"
        );
        return array_map(fn($table) => $table->tablename, $tables);
    }

    protected function getSQLiteTables($connection): array
    {
        $tables = $connection->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'"
        );
        return array_map(fn($table) => $table->name, $tables);
    }

    protected function getGenericTables(): array
    {
        return Schema::connection($this->connection)->getAllTables();
    }

    /**
     * Get table schema details
     */
    public function getTableSchema(string $table): string
    {
        $connection = DB::connection($this->connection);
        $driver = $connection->getDriverName();

        return match ($driver) {
            'mysql' => $this->getMySQLTableSchema($connection, $table),
            'pgsql' => $this->getPostgreSQLTableSchema($connection, $table),
            'sqlite' => $this->getSQLiteTableSchema($connection, $table),
            default => $this->getGenericTableSchema($table),
        };
    }

    protected function getMySQLTableSchema($connection, string $table): string
    {
        $schema = "Table: {$table}\n";
        $columns = $connection->select("DESCRIBE {$table}");

        foreach ($columns as $column) {
            $schema .= "  - {$column->Field} ({$column->Type})";

            if ($column->Key === 'PRI') {
                $schema .= " PRIMARY KEY";
            }
            if ($column->Null === 'NO') {
                $schema .= " NOT NULL";
            }
            if ($column->Default !== null) {
                $schema .= " DEFAULT {$column->Default}";
            }

            $schema .= "\n";
        }

        return $schema;
    }

    protected function getPostgreSQLTableSchema($connection, string $table): string
    {
        $schema = "Table: {$table}\n";
        $columns = $connection->select(
            "SELECT column_name, data_type, is_nullable, column_default 
             FROM information_schema.columns 
             WHERE table_name = ? 
             ORDER BY ordinal_position",
            [$table]
        );

        foreach ($columns as $column) {
            $schema .= "  - {$column->column_name} ({$column->data_type})";

            if ($column->is_nullable === 'NO') {
                $schema .= " NOT NULL";
            }
            if ($column->column_default !== null) {
                $schema .= " DEFAULT {$column->column_default}";
            }

            $schema .= "\n";
        }

        return $schema;
    }

    protected function getSQLiteTableSchema($connection, string $table): string
    {
        $schema = "Table: {$table}\n";
        $columns = $connection->select("PRAGMA table_info({$table})");

        foreach ($columns as $column) {
            $schema .= "  - {$column->name} ({$column->type})";

            if ($column->pk == 1) {
                $schema .= " PRIMARY KEY";
            }
            if ($column->notnull == 1) {
                $schema .= " NOT NULL";
            }
            if ($column->dflt_value !== null) {
                $schema .= " DEFAULT {$column->dflt_value}";
            }

            $schema .= "\n";
        }

        return $schema;
    }

    protected function getGenericTableSchema(string $table): string
    {
        $schema = "Table: {$table}\n";
        $columns = Schema::connection($this->connection)->getColumnListing($table);

        foreach ($columns as $column) {
            $schema .= "  - {$column}\n";
        }

        return $schema;
    }

    /**
     * Find relevant tables for a question
     */
    public function findRelevantTables(string $question, AiService $ai): array
    {
        $allTables = $this->getAllTables();
        $maxTables = config('ai-database.max_tables', 15);

        if (count($allTables) <= $maxTables) {
            return $allTables;
        }

        // Use AI to find relevant tables
        $tablesList = implode(', ', $allTables);
        $prompt = "Given these database tables: {$tablesList}\n\n";
        $prompt .= "Question: \"{$question}\"\n\n";
        $prompt .= "Which tables are most relevant to answer this question?\n";
        $prompt .= "Return ONLY the table names as a comma-separated list, nothing else.";

        $response = $ai->complete($prompt);

        // Parse response
        $tables = array_map('trim', explode(',', $response));

        // Filter to only valid tables
        return array_values(array_intersect($tables, $allTables));
    }
}