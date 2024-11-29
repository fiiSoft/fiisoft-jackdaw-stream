<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry;

use FiiSoft\Jackdaw\Registry\Reader\DefaultReader;
use FiiSoft\Jackdaw\Registry\Writer\FullWriter;
use FiiSoft\Jackdaw\Registry\Writer\KeyWriter;
use FiiSoft\Jackdaw\Registry\Writer\ValueWriter;

final class Registry
{
    private static ?self $shared = null;
    
    private Storage $storage;
    
    public static function shared(): self
    {
        if (self::$shared === null) {
            self::$shared = new self();
        }
        
        return self::$shared;
    }
    
    public static function new(): self
    {
        return new self();
    }
    
    public function __construct()
    {
        $this->storage = new Storage();
    }
    
    public function value(string $name): RegWriter
    {
        return new ValueWriter($this->storage, $name);
    }
    
    public function key(string $name): RegWriter
    {
        return new KeyWriter($this->storage, $name);
    }
    
    public function valueKey(string $value = 'value', string $key = 'key'): ValueKeyWriter
    {
        return new FullWriter($this->storage, $value, $key);
    }
    
    /**
     * @param mixed|null $orElse default value if variable under $name is null
     */
    public function read(string $name, $orElse = null): RegReader
    {
        return new DefaultReader($this->storage, $name, $orElse);
    }
    
    /**
     * Allows to read last registered value immediately.
     *
     * @param mixed|null $orElse
     * @return mixed|null
     */
    public function get(string $name, $orElse = null)
    {
        return $this->storage->registered[$name] ?? $orElse;
    }
    
    /**
     * Allows to set value for given name immediately.
     *
     * @param mixed|null $value
     * @return $this fluent interface
     */
    public function set(string $name, $value): self
    {
        $this->storage->registered[$name] = $value;
        
        return $this;
    }
    
    /**
     * @param mixed $initialValue
     */
    public function entry(int $mode, $initialValue = null): RegEntry
    {
        return new RegEntry($this->storage, $mode, $initialValue);
    }
}