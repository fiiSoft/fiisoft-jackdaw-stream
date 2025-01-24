<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\ReindexKeys;

use FiiSoft\Jackdaw\Mapper\Internal\StatelessMapper;

final class ReindexKeysSimple extends StatelessMapper
{
    /**
     * @return array<int, mixed>
     */
    public function map($value, $key = null): array
    {
        return \array_values($value);
    }
    
    /**
     * @inheritDoc
     */
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => \array_values($value);
        }
    }
}