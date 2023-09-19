<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Increment extends BaseMapper
{
    private int $step;
    
    public function __construct(int $step)
    {
        if ($step === 0) {
            throw new \InvalidArgumentException('Invalid param step - it cannot be 0');
        }
        
        $this->step = $step;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_int($value)) {
            return $value + $this->step;
        }
        
        throw new \LogicException('Mapper Increment requires integers');
    }
}