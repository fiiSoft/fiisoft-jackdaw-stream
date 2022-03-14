<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;

final class Round implements Mapper
{
    private int $precision;
    
    public function __construct(int $precision = 2)
    {
        $this->precision = $precision;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_float($value)) {
            return \round($value, $this->precision);
        }
    
        if (\is_int($value)) {
            return $value;
        }
    
        if (\is_numeric($value)) {
            return \round((float) $value, $this->precision);
        }
        
        throw new \LogicException('Unable to round non-number value '.Helper::typeOfParam($value));
    }
}