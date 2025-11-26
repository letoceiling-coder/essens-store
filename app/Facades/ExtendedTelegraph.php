<?php

namespace App\Facades;

use App\Services\ExtendedTelegraph;
use Illuminate\Support\Facades\Facade;

/**
 * Фасад для ExtendedTelegraph
 * 
 * @method static \App\Services\ExtendedTelegraph bot(\DefStudio\Telegraph\Models\TelegraphBot|string $bot)
 * @method static \App\Services\ExtendedTelegraph chat(\DefStudio\Telegraph\Models\TelegraphChat|string $chat)
 * @method static \DefStudio\Telegraph\Client\TelegraphResponse send()
 * @method static \Illuminate\Foundation\Bus\PendingDispatch dispatch(?string $queue = null)
 * 
 * @see \App\Services\ExtendedTelegraph
 */
class ExtendedTelegraphFacade extends Facade
{
    /**
     * Получить зарегистрированное имя компонента.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return ExtendedTelegraph::class;
    }
}

