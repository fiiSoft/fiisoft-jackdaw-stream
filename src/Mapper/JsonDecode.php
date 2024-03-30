<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class JsonDecode extends StateMapper
{
    private bool $associative;
    private int $flags;
    
    public function __construct(?int $flags = null, bool $associative = true)
    {
        $this->associative = $associative;
        $this->flags = Helper::jsonFlags($flags);
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return \json_decode($value, $this->associative, 512, $this->flags);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => \json_decode($value, $this->associative, 512, $this->flags);
        }
    }
}