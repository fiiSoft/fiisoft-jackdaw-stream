<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Writer;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Registry\RegWriter;
use FiiSoft\Jackdaw\Registry\Storage;

final class FullWriter implements RegWriter
{
    private Storage $storage;
    
    private string $value;
    private string $key;
    
    public function __construct(Storage $storage, string $value, string $key)
    {
        if ($key === $value) {
            throw new \InvalidArgumentException('Parameters value and key cannot be the same');
        }
        
        $this->storage = $storage;
        $this->value = $value;
        $this->key = $key;
    }
    
    public function remember(Item $item): void
    {
        $this->storage->registered[$this->key] = $item->key;
        $this->storage->registered[$this->value] = $item->value;
    }
}