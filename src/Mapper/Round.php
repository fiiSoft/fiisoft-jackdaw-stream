<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Round extends BaseMapper
{
    private int $precision;
    
    public function __construct(int $precision = 2)
    {
        if ($precision < 0 || $precision > 16) {
            throw new \InvalidArgumentException('Invalid param precision');
        }
        
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
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self) {
            if ($other->precision < $this->precision) {
                $this->precision = $other->precision;
            }
            
            return true;
        }
        
        return false;
    }
}