<?php

namespace Mostafaarafat\AiDatabase\Services;

class AnswerService
{
    protected AiService $ai;

    public function __construct(AiService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Generate human-readable answer from query results
     */
    public function generateAnswer(string $question, string $sql, array $results): string
    {
        $prompt = $this->buildPrompt($question, $sql, $results);

        return $this->ai->complete($prompt);
    }

    /**
     * Build prompt for answer generation
     */
    protected function buildPrompt(string $question, string $sql, array $results): string
    {
        $resultsJson = json_encode($results, JSON_PRETTY_PRINT);

        return view('ai-database::prompts.answer', [
            'question' => $question,
            'sql' => $sql,
            'results' => $resultsJson,
            'count' => count($results),
        ])->render();
    }

    /**
     * Format results for simple display
     */
    public function formatResults(array $results): string
    {
        if (empty($results)) {
            return 'No results found.';
        }

        // Single value result
        if (count($results) === 1 && count((array)$results[0]) === 1) {
            $value = array_values((array)$results[0])[0];
            return "Result: {$value}";
        }

        // Multiple results
        return 'Found ' . count($results) . ' result(s).';
    }
}