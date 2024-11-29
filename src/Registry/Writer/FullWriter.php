<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Writer;

use FiiSoft\Jackdaw\Registry\Exception\RegistryExceptionFactory;
use FiiSoft\Jackdaw\Registry\Reader\DefaultReader;
use FiiSoft\Jackdaw\Registry\RegReader;
use FiiSoft\Jackdaw\Registry\Storage;
use FiiSoft\Jackdaw\Registry\ValueKeyWriter;

final class FullWriter implements ValueKeyWriter
{
    private Storage $storage;
    
    private string $value;
    private string $key;
    
    private ?RegReader $valueReader = null;
    private ?RegReader $keyReader = null;
    
    public function __construct(Storage $storage, string $value, string $key)
    {
        if ($key === $value) {
            throw RegistryExceptionFactory::parametersValueAndKeyCannotBeTheSame();
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
            throw RegistryExceptionFactory::cannotSetValue();
        }
    }
    
    public function value(): RegReader
    {
        if ($this->valueReader === null) {
            $this->valueReader = new DefaultReader($this->storage, $this->value);
        }
        
        return $this->valueReader;
    }
    
    public function key(): RegReader
    {
        if ($this->keyReader === null) {
            $this->keyReader = new DefaultReader($this->storage, $this->key);
        }
        
        return $this->keyReader;
    }
}