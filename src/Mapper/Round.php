<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Mapper\Exception\MapperExceptionFactory;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Round extends StateMapper
{
    private int $precision;
    
    public function __construct(int $precision = 2)
    {
        if ($precision < 0 || $precision > 16) {
            throw InvalidParamException::describe('precision', $precision);
        }
        
        $this->precision = $precision;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
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
        
        throw MapperExceptionFactory::unableToRoundValue($value);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_float($value)) {
                yield $key => \round($value, $this->precision);
            } elseif (\is_int($value)) {
                yield $key => $value;
            } elseif (\is_numeric($value)) {
                yield $key => \round((float) $value, $this->precision);
            } else {
                throw MapperExceptionFactory::unableToRoundValue($value);
            }
        }
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