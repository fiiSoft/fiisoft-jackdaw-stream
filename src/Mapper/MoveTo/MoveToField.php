<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\MoveTo;

use FiiSoft\Jackdaw\Mapper\MoveTo;

final class MoveToField extends MoveTo
{
    /**
     * @param mixed $value
     * @param mixed $key
     * @return array<string|int, mixed>
     */
    public function map($value, $key = null): array
    {
        return [$this->field => $value];
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => [$this->field => $value];
        }
    }
}