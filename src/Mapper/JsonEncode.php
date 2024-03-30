<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class JsonEncode extends StateMapper
{
    private int $flags;
    
    public function __construct(?int $flags = null)
    {
        $this->flags = Helper::jsonFlags($flags);
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