<?php

namespace Mostafaarafat\AiDatabase\Commands;

use Illuminate\Console\Command;
use Mostafaarafat\AiDatabase\Services\QueryService;
use Mostafaarafat\AiDatabase\Services\AnswerService;

class AskDatabaseCommand extends Command
{
    protected $signature = 'db:ask {question} {--connection=} {--sql-only} {--details}';
    protected $description = 'Ask a question about your database using AI';

    public function handle(QueryService $queryService, AnswerService $answerService): int
    {
        $question = $this->argument('question');
        $connection = $this->option('connection');
        $sqlOnly = $this->option('sql-only');
        $showDetails = $this->option('details'); // Changed from 'verbose'

        try {
            $this->info("🤖 Processing: {$question}");
            $this->newLine();

            // Generate SQL
            if ($showDetails) {
                $this->line('Generating SQL query...');
            }

            $sql = $queryService->generateQuery($question);

            if ($sqlOnly) {
                $this->line($sql);
                return self::SUCCESS;
            }

            if ($showDetails) {
                $this->info("Generated SQL:");
                $this->line($sql);
                $this->newLine();
            }

            // Execute query
            if ($showDetails) {
                $this->line('Executing query...');
            }

            $results = $queryService->execute($sql);

            if ($showDetails) {
                $this->info("Found " . count($results) . " result(s)");
                $this->newLine();
            }

            // Generate answer
            if ($showDetails) {
                $this->line('Generating human-readable answer...');
            }

            $answer = $answerService->generateAnswer($question, $sql, $results);

            $this->newLine();
            $this->info("📊 Answer:");
            $this->line($answer);
            $this->newLine();

            if ($showDetails) {
                $this->info("Raw Results:");
                $this->table(
                    array_keys((array)$results[0] ?? []),
                    array_map(fn($row) => (array)$row, $results)
                );
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}