<?php

declare(strict_types=1);

namespace AlexanderPoellmann\LaravelPostPlc\Facades;

use AlexanderPoellmann\LaravelPostPlc\Enums\ServiceMethods;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use RicorocksDigitalAgency\Soap\Response\Response;
use Spatie\LaravelData\Data;

/**
 * @see \AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc
 *
 * @method static string endpoint()
 * @method static string getIdentifier()
 * @method static string getClientId()
 * @method static string getOrgUnitId()
 * @method static string getOrgUnitGuid()
 * @method static bool isConfigured()
 * @method static void assertConfigured()
 * @method static Response request(ServiceMethods $method, Data|array $data, bool $asRow = false)
 * @method static void call(ServiceMethods $method, Data|array $data, bool $as_row = false)
 * @method static null|Response getResponse()
 * @method static null|ServiceMethods lastMethod()
 * @method static array toArray()
 * @method static Collection toCollection()
 * @method static Data toData(string $dataClass)
 * @method static Data toObject()
 */
class LaravelPostPlc extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \AlexanderPoellmann\LaravelPostPlc\LaravelPostPlc::class;
    }
}
