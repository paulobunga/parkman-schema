<?php

namespace Paulobunga\ParkmanSchema;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Paulobunga\ParkmanSchema\Skeleton\SkeletonClass
 */
class ParkmanSchemaFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'parkman-schema';
    }
}
