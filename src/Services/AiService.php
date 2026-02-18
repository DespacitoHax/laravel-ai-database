<?php

namespace Mostafaarafat\AiDatabase\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Mostafaarafat\AiDatabase\Contracts\AiProviderInterface;

class AiService implements AiProviderInterface
{
    protected string $provider;
    protected array $config;

    public function __construct(?string $provider = null)
    {
        $this->provider = $provider ?? config('ai-database.default');
        $this->config = config("ai-database.providers.{$this->provider}");

        if (!$this->isConfigured()) {
            throw new Exception("AI provider '{$this->provider}' is not properly configured.");
        }
    }

    public function complete(string $prompt, array $options = []): string
    {
        return match ($this->provider) {
            'anthropic' => $this->completeWithAnthropic($prompt, $options),
            'openai' => $this->completeWithOpenAI($prompt, $options),
            'gemini' => $this->completeWithGemini($prompt, $options),
            default => throw new Exception("Unsupported provider: {$this->provider}"),
        };
    }

    protected function completeWithAnthropic(string $prompt, array $options): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->config['api_key'],
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $options['model'] ?? $this->config['model'],
            'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'],
            'temperature' => $options['temperature'] ?? $this->config['temperature'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ]);

        if (!$response->successful()) {
            throw new Exception("Anthropic API request failed: {$response->body()}");
        }

        return $response->json()['content'][0]['text'];
    }

    protected function completeWithOpenAI(string $prompt, array $options): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $options['model'] ?? $this->config['model'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a SQL expert. Generate accurate and efficient SQL queries.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => $options['temperature'] ?? $this->config['temperature'],
            'max_tokens' => $options['max_tokens'] ?? $this->config['max_tokens'],
        ]);

        if (!$response->successful()) {
            throw new Exception('OpenAI API error: ' . $response->body());
        }

        return $response->json()['choices'][0]['message']['content'];
    }

    protected function completeWithGemini(string $prompt, array $options): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->config['model']}:generateContent?key={$this->config['api_key']}";

        $response = Http::timeout(60)->post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? $this->config['temperature'],
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception('Gemini API error: ' . $response->body());
        }

        return $response->json()['candidates'][0]['content']['parts'][0]['text'];
    }

    public function isConfigured(): bool
    {
        return !empty($this->config['api_key']);
    }

    public function getName(): string
    {
        return $this->provider;
    }
}