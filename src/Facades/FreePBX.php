<?php

declare(strict_types=1);

namespace HyEnergySolutions\FreePBX\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection getExtensions()
 * @method static Collection getRingGroups()
 * @method static Collection getCdrs(int $first = 100)
 * @method static Collection getCallFlows()
 * @method static ?string getCallFlowState(string $id)
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
