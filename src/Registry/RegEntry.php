<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;
use FiiSoft\Jackdaw\Memo\MemoReader;
use FiiSoft\Jackdaw\Registry\Exception\RegistryExceptionFactory;
use FiiSoft\Jackdaw\Registry\Reader\DefaultReader;
use FiiSoft\Jackdaw\Registry\Reader\TupleReader;
use FiiSoft\Jackdaw\Registry\Writer\KeyWriter;
use FiiSoft\Jackdaw\Registry\Writer\TupleWriter;
use FiiSoft\Jackdaw\Registry\Writer\ValueWriter;

final class RegEntry implements ValueKeyWriter, RegReader
{
    private Storage $storage;
    private RegWriter $writer;
    
    private string $name;
    private int $mode;
    
    private ?RegReader $valueReader = null;
    private ?RegReader $keyReader = null;
    
    /**
     * @param mixed|null $initialValue
     */
    public function __construct(Storage $storage, int $mode, $initialValue = null)
    {
        $this->storage = $storage;
        
        $this->setMode($mode);
        $this->createRandomName();
        $this->createWriter();
        
        $this->set($initialValue);
    }
    
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->writer->write($value, $key);
    }
    
    /**
     * @inheritDoc
     */
    public function set($value): void
    {
        $this->writer->set($value);
    }
    
    /**
     * @inheritDoc
     */
    public function read()
    {
        return $this->storage->registered[$this->name] ?? null;
    }
    
    /**
     * Alias for read() for convenient use.
     *
     * @return mixed|null
     */
    public function get()
    {
        return $this->storage->registered[$this->name] ?? null;
    }
    
    public function value(): RegReader
    {
        if ($this->valueReader === null) {
            $this->valueReader = $this->createReader('value');
        }
        
        return $this->valueReader;
    }
    
    public function key(): RegReader
    {
        if ($this->keyReader === null) {
            $this->keyReader = $this->createReader('key');
        }
        
        return $this->keyReader;
    }
    
    public function equals(MemoReader $other): bool
    {
        return $other === $this;
    }
    
    private function createReader(string $type): RegReader
    {
        if ($this->mode === Check::BOTH) {
            return new TupleReader($this->storage, $this->name, $type === 'value');
        }
        
        if ($type === 'value' && $this->mode === Check::VALUE
            || $type === 'key' && $this->mode === Check::KEY
        ) {
            return new DefaultReader($this->storage, $this->name);
        }

        throw RegistryExceptionFactory::cannotCreateReaderOfType($type, $this->mode);
    }
    
    private function setMode(int $mode): void
    {
        $this->mode = Mode::get($mode);
        
        if ($this->mode === Check::ANY) {
            $this->mode = Check::BOTH;
        }
    }
    
    private function createRandomName(): void
    {
        do {
            $this->name = \str_shuffle('qwertyuiopasdfghjklzxcvbnm1234567890QWERTYUIOPASDFGHJKLZXCVNBM');
        } while (\array_key_exists($this->name, $this->storage->registered));
    }
    
    private function createWriter(): void
    {
        switch ($this->mode) {
            case Check::VALUE:
                $this->writer = new ValueWriter($this->storage, $this->name);
            break;
            case Check::KEY:
                $this->writer = new KeyWriter($this->storage, $this->name);
            break;
            default:
                $this->writer = new TupleWriter($this->storage, $this->name);
        }
    }
}