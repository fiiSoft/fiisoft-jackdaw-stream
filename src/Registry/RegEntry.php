<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Registry\Writer\KeyWriter;
use FiiSoft\Jackdaw\Registry\Writer\TupleWriter;
use FiiSoft\Jackdaw\Registry\Writer\ValueWriter;

final class RegEntry implements RegWriter, RegReader
{
    private Storage $storage;
    private RegWriter $writer;
    
    private string $name;
    private int $mode;
    
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
    public function read()
    {
        return $this->storage->registered[$this->name] ?? null;
    }
    
    /**
     * @inheritDoc
     */
    public function set($value): void
    {
        $this->writer->set($value);
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
    
    private function setMode(int $mode): void
    {
        $this->mode = Check::getMode($mode);
        
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