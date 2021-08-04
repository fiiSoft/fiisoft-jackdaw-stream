<?php

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Internal\ResultProvider;

interface Reducer extends ResultProvider
{
    /**
     * @param mixed $value
     * @param string|int|null $key
     * @return void
     */
    public function consume($value, $key = null);
    
    /**
     * @return mixed|null
     */
    public function result();
}