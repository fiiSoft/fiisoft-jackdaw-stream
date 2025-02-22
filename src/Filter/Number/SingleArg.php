<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Filter;

abstract class SingleArg extends NumberFilter
{
    /** @var float|int */
    protected $number;
    
    /**
     * @param float|int $value
     */
    abstract protected static function create(int $mode, $value): self;
    
    /**
     * @param float|int $value
     */
    final protected function __construct(int $mode, $value)
    {
        parent::__construct($mode);
        
        if (\is_int($value) || \is_float($value)) {
            $this->number = $value;
        } else {
            throw InvalidParamException::describe('value', $value);
        }
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode, $this->number)
            : $this;
    }
    
    final public function equals(Filter $other): bool
    {
        return $other instanceof $this
            && $other->number === $this->number
            && parent::equals($other);
    }
}