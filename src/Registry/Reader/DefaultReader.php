<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Reader;

use FiiSoft\Jackdaw\Memo\MemoReader;
use FiiSoft\Jackdaw\Registry\RegReader;
use FiiSoft\Jackdaw\Registry\Storage;

final class DefaultReader implements RegReader
{
    private Storage $storage;
    
    private string $name;
    
    /** @var mixed|null */
    private $orElse;
    
    /**
     * @param mixed|null $orElse
     */
    public function __construct(Storage $storage, string $name, $orElse = null)
    {
        $this->storage = $storage;
        $this->name = $name;
        $this->orElse = $orElse;
    }
    
    /**
     * @inheritDoc
     */
    public function read()
    {
        return $this->storage->registered[$this->name] ?? $this->orElse;
    }
    
    public function equals(MemoReader $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->name === $this->name
            && $other->orElse === $this->orElse
            && $other->storage === $this->storage;
    }
}