<?php

namespace Mostafaarafat\AiDatabase\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string askForQuery(string $question, ?string $connection = null)
 * @method static string ask(string $question, ?string $connection = null)
 * @method static array askDetailed(string $question, ?string $connection = null)
 */
class AiDatabase extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ai-database';
    }
}