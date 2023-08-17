<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Segregate;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Item;

final class Bucket implements Destroyable
{
    public ?Item $item = null;
    public array $data = [];
    
    private bool $reindex;
    
    public function __construct(bool $reindex = false, ?Item $item = null)
    {
        $this->reindex = $reindex;
        
        if ($item !== null) {
            $this->add($item);
        }
    }
    
    public function append(Item $item): Bucket
    {
        return new self($this->reindex, $item);
    }
    
    public function prepend(Item $item): Bucket
    {
        return new self($this->reindex, $item);
    }
    
    public function add(Item $item): void
    {
        if ($this->item === null) {
            $this->item = $item->copy();
        }
        
        if ($this->reindex) {
            $this->data[] = $item->value;
        } else {
            $this->data[$item->key] = $item->value;
        }
    }
    
    public function replace(Item $item): void
    {
        $this->clear();
        $this->add($item);
    }
    
    public function clear(): void
    {
        $this->data = [];
        $this->item = null;
    }
    
    public function destroy(): void
    {
        $this->item = null;
        $this->data = [];
    }
}