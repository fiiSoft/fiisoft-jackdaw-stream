<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry;

use FiiSoft\Jackdaw\Consumer\ConsumerReady;

interface RegWriter extends ConsumerReady
{
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function write($value, $key): void;
    
    /**
     * @param mixed $value
     */
    public function set($value): void;
}