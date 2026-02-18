<?php

namespace Mostafaarafat\AiDatabase\Contracts;

interface AiProviderInterface
{
    /**
     * Send a completion request to the AI provider
     */
    public function complete(string $prompt, array $options = []): string;

    /**
     * Get the provider name
     */
    public function getName(): string;

    /**
     * Check if the provider is configured
     */
    public function isConfigured(): bool;
}