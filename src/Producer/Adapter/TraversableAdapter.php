<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class TraversableAdapter extends BaseProducer
{
    /** @var \Traversable<string|int, mixed> */
    private \Traversable $iterator;
    
    /**
     * @param \Traversable<string|int, mixed> $iterator
     */
    public function __construct(\Traversable $iterator)
    {
        $this->iterator = $iterator;
    }
    
    public function getIterator(): \Traversable
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