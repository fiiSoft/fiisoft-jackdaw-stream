<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class JsonEncode extends StateMapper
{
    private int $flags;
    
    public function __construct(?int $flags = null)
    {
        $this->flags = $flags ?? \JSON_THROW_ON_ERROR;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): string
    {
        return \json_encode($value, $this->flags);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => \json_encode($value, $this->flags);
        }
    }
}