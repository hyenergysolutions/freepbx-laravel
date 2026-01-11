<?php

declare(strict_types=1);

namespace HyEnergySolutions\FreePBX\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection getExtensions()
 * @method static Collection getCallFlows()
 * @method static Collection getQueues()
 *
 * @see \HyEnergySolutions\FreePBX\FreePBX
 */
class FreePBX extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \HyEnergySolutions\FreePBX\FreePBX::class;
    }
}
