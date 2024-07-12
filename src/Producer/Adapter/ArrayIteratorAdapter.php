<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class ArrayIteratorAdapter extends BaseProducer
{
    /** @var \ArrayIterator<string|int, mixed> */
    private \ArrayIterator $iterator;
    
    /**
     * @param \ArrayIterator<string|int, mixed> $iterator
     */
    public function __construct(\ArrayIterator $iterator)
    {
        $this->iterator = $iterator;
    }
    
    public function getIterator(): \Iterator
    {
        return $this->iterator;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->iterator = new \ArrayIterator();
            
            parent::destroy();
        }
    }
}