<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Writer;

final class KeyWriter extends SingleWriter
{
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->storage->registered[$this->name] = $key;
    }
    
    /**
     * @inheritDoc
     */
    public function set($value): void
    {
        $this->storage->registered[$this->name] = $value;
    }
}