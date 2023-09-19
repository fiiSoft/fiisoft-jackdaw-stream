<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Writer;

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
    
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->storage->registered[$this->key] = $key;
        $this->storage->registered[$this->value] = $value;
    }
    
    /**
     * @inheritDoc
     */
    public function set($value): void
    {
        if ($value === null) {
            $this->storage->registered[$this->key] = $value;
            $this->storage->registered[$this->value] = $value;
        } elseif (\is_array($value)) {
            [$key, $value] = $value;
            $this->storage->registered[$this->key] = $key;
            $this->storage->registered[$this->value] = $value;
        } else {
            throw new \InvalidArgumentException('FullWriter requires null or tuple [key,value] to set directly');
        }
    }
}