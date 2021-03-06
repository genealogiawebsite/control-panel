<?php

namespace LaravelEnso\ControlPanel;

use LaravelEnso\ControlPanel\Enums\ApplicationTypes;
use LaravelEnso\Enums\EnumServiceProvider as ServiceProvider;

class EnumServiceProvider extends ServiceProvider
{
    public $register = [
        'applicationTypes' => ApplicationTypes::class,
    ];
}
