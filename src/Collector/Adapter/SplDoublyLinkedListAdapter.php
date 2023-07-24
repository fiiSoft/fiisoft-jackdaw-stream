<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter;

use FiiSoft\Jackdaw\Collector\Collector;

final class SplDoublyLinkedListAdapter implements Collector
{
    private \SplDoublyLinkedList $list;
    
    public function __construct(\SplDoublyLinkedList $list)
    {
        $this->list = $list;
    }
    
    /**
     * @inheritDoc
     */
    public function set($key, $value): void
    {
        throw new \LogicException('You cannot keep keys and values in '.\get_class($this->list));
    }
    
    /**
     * @inheritDoc
     */
    public function add($value): void
    {
        $this->list[] = $value;
    }
    
    public function canPreserveKeys(): bool
    {
        return false;
    }
    
    public function allowKeys(?bool $allowKeys): void
    {
        //noop
    }
}