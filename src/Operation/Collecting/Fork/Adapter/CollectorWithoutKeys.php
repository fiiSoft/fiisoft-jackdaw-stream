<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting\Fork\Adapter;

final class CollectorWithoutKeys extends CollectorFork
{
    /**
     * @inheritDoc
     */
    public function accept($value, $key): void
    {
        $this->collector->add($value);
    }
}