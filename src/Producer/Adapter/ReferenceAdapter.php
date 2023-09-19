<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\NonCountableProducer;

final class ReferenceAdapter extends NonCountableProducer
{
    /** @var mixed */
    private $variable;
    
    private int $index = 0;
    
    /**
     * @param mixed $variable REFERENCE
     */
    public function __construct(&$variable)
    {
        $this->variable = &$variable;
    }
    
    public function feed(Item $item): \Generator
    {
        while ($this->variable !== null) {
            $item->key = $this->index++;
            $item->value = $this->variable;
            
            yield;
        }
    }
}