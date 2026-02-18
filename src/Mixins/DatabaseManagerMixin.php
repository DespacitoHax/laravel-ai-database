<?php

namespace Mostafaarafat\AiDatabase\Mixins;

use Mostafaarafat\AiDatabase\Services\QueryService;
use Mostafaarafat\AiDatabase\Services\AnswerService;
use Mostafaarafat\AiDatabase\Services\AiService;
use Mostafaarafat\AiDatabase\Services\SchemaService;

/**
 * @mixin \Illuminate\Database\DatabaseManager
 */
class DatabaseManagerMixin
{
    /**
     * Ask AI for SQL query
     */
    public function askForQuery()
    {
        return function (string $question, ?string $connection = null): string {
            $queryService = app(QueryService::class);

            if ($connection) {
                $queryService = new QueryService(
                    app(AiService::class),
                    new SchemaService($connection),
                    $connection
                );
            }

            return $queryService->generateQuery($question);
        };
    }

    /**
     * Ask AI and get human answer
     */
    public function ask()
    {
        return function (string $question, ?string $connection = null): string {
            $queryService = app(QueryService::class);
            $answerService = app(AnswerService::class);

            if ($connection) {
                $queryService = new QueryService(
                    app(AiService::class),
                    new SchemaService($connection),
                    $connection
                );
            }

            // Generate and execute query
            $sql = $queryService->generateQuery($question);
            $results = $queryService->execute($sql);

            // Generate answer
            return $answerService->generateAnswer($question, $sql, $results);
        };
    }

    /**
     * Ask AI and get detailed response
     */
    public function askDetailed()
    {
        return function (string $question, ?string $connection = null): array {
            $queryService = app(QueryService::class);
            $answerService = app(AnswerService::class);

            if ($connection) {
                $queryService = new QueryService(
                    app(AiService::class),
                    new SchemaService($connection),
                    $connection
                );
            }

            // Generate and execute query
            $sql = $queryService->generateQuery($question);
            $results = $queryService->execute($sql);
            $answer = $answerService->generateAnswer($question, $sql, $results);

            return [
                'question' => $question,
                'sql' => $sql,
                'results' => $results,
                'answer' => $answer,
                'rows' => count($results),
            ];
        };
    }
}