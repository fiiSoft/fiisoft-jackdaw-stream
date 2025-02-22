<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Reader;

use FiiSoft\Jackdaw\Memo\MemoReader;
use FiiSoft\Jackdaw\Registry\RegReader;
use FiiSoft\Jackdaw\Registry\Storage;

final class TupleReader implements RegReader
{
    private Storage $storage;
    private string $name;
    private int $index;
    
    public function __construct(Storage $storage, string $name, bool $readValue)
    {
        $this->name = $name;
        $this->storage = $storage;
        $this->index = $readValue ? 1 : 0;
    }
    
    /**
     * @inheritDoc
     */
    public function read()
    {
        return $this->storage->registered[$this->name][$this->index];
    }
    
    public function equals(MemoReader $other): bool
    {
        return $other instanceof $this
            && $other->name === $this->name
            && $other->index === $this->index
            && $other->storage === $this->storage;
    }
}