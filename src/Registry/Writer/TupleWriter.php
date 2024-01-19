<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Registry\Writer;

use FiiSoft\Jackdaw\Registry\Exception\RegistryExceptionFactory;

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
            throw RegistryExceptionFactory::cannotSetValue();
        }
    }
}