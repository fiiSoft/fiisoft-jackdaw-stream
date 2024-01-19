<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Increment extends StateMapper
{
    private int $step;
    
    public function __construct(int $step)
    {
        if ($step === 0) {
            throw InvalidParamException::byName('step');
        }
        
        $this->step = $step;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): int
    {
        return $value + $this->step;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => $value + $this->step;
        }
    }
}