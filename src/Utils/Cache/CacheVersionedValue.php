<?php

namespace App\Utils\Cache;

class CacheVersionedValue
{
    public $version, $value;

    public function __construct($version, $value)
    {
        $this->version = $version;
        $this->value = $value;
    }

    public function Resolve()
    {
        $x = $this->value;
        return $this->value = call_user_func($x);
    }
}