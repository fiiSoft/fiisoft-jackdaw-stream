<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter;

final class CollectorWithKeys extends CollectorFork
{
    /**
     * @inheritDoc
     */
    public function accept($value, $key): void
    {
        $this->collector->set($key, $value);
    }
}