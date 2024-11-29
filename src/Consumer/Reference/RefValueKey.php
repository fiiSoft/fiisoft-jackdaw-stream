<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Reference;

use FiiSoft\Jackdaw\Consumer\Consumer;

final class RefValueKey implements Consumer
{
    /** @var mixed REFERENCE */
    private $value;
    
    /** @var mixed REFERENCE */
    private $key;
    
    /**
     * @param mixed $value REFERENCE
     * @param mixed $key REFERENCE
     */
    public function __construct(&$value, &$key)
    {
        $this->value = &$value;
        $this->key = &$key;
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        $this->value = $value;
        $this->key = $key;
    }
}