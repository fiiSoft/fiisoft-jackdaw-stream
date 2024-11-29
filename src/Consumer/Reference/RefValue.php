<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Reference;

use FiiSoft\Jackdaw\Consumer\Consumer;

final class RefValue implements Consumer
{
    /** @var mixed REFERENCE */
    private $value;
    
    /**
     * @param mixed $value REFERENCE
     */
    public function __construct(&$value)
    {
        $this->value = &$value;
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        $this->value = $value;
    }
}