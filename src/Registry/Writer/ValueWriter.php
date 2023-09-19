<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Writer;

final class ValueWriter extends SingleWriter
{
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->storage->registered[$this->name] = $value;
    }
    
    /**
     * @inheritDoc
     */
    public function set($value): void
    {
        $this->storage->registered[$this->name] = $value;
    }
}