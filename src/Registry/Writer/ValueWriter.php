<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Writer;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Registry\RegWriter;
use FiiSoft\Jackdaw\Registry\Storage;

final class ValueWriter implements RegWriter
{
    private Storage $storage;
    
    private string $name;
    
    public function __construct(Storage $storage, string $name)
    {
        $this->storage = $storage;
        $this->name = $name;
    }
    
    public function remember(Item $item): void
    {
        $this->storage->registered[$this->name] = $item->value;
    }
}