<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Segregate;

use FiiSoft\Jackdaw\Internal\Destroyable;
use FiiSoft\Jackdaw\Internal\Item;

final class Bucket implements Destroyable
{
    public ?Item $item = null;
    
    /** @var array<string|int, mixed> */
    public array $data = [];
    
    private int $limit;
    private bool $reindex;
    
    public function __construct(bool $reindex = false, ?int $limit = null, ?Item $item = null)
    {
        $this->reindex = $reindex;
        $this->limit = $limit ?? \PHP_INT_MAX;
        
        if ($item !== null) {
            $this->add($item);
        }
    }
    
    public function create(Item $item): Bucket
    {
        return new Bucket($this->reindex, $this->limit, $item);
    }
    
    public function add(Item $item): void
    {
        if ($this->item === null) {
            $this->item = clone $item;
        }
        
        if (\count($this->data) < $this->limit) {
            if ($this->reindex) {
                $this->data[] = $item->value;
            } else {
                $this->data[$item->key] = $item->value;
            }
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
        $this->data = [];
        $this->item = null;
        $this->limit = 0;
    }
}