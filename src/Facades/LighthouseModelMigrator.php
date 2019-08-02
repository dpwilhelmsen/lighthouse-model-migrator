<?php

namespace DanielWilhelmsen\LighthouseModelMigrator\Facades;

use Illuminate\Support\Facades\Facade;

class LighthouseModelMigrator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'lighthousemodelmigrator';
    }
}
