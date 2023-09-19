<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Writer;

final class TupleWriter extends SingleWriter
{
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->storage->registered[$this->name] = [$key, $value];
    }
    
    /**
     * @inheritDoc
     */
    public function set($value): void
    {
        if (\is_array($value) || $value === null) {
            $this->storage->registered[$this->name] = $value;
        } else {
            throw new \InvalidArgumentException('Invalid param value - null or tuple [key,value] is required');
        }
    }
}