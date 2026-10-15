<?php

use App\Support\Settings;

if (! function_exists('setting')) {
    /**
     * Read a system setting, falling back to its default.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return Settings::get($key, $default);
    }
}
