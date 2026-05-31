<?php

namespace BonusBall\Laravel\Facades;

use BonusBall\Client\Resources\ClientResource;
use BonusBall\Client\Resources\ReasonResource;
use BonusBall\Client\Resources\TransactionResource;
use BonusBall\Laravel\BonusBallManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ClientResource clients()
 * @method static TransactionResource transactions()
 * @method static ReasonResource reasons()
 *
 * @see BonusBallManager
 */
class BonusBall extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BonusBallManager::class;
    }
}
