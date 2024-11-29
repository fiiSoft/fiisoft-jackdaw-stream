<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Reference;

use FiiSoft\Jackdaw\Consumer\Consumer;

final class RefKey implements Consumer
{
    /** @var mixed REFERENCE */
    private $key;
    
    /**
     * @param mixed $key REFERENCE
     */
    public function __construct(&$key)
    {
        $this->key = &$key;
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        $this->key = $key;
    }
}