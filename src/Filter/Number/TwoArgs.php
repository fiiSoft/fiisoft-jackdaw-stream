<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Exception\FilterExceptionFactory;
use FiiSoft\Jackdaw\Filter\Filter;

abstract class TwoArgs extends NumberFilter
{
    /** @var float|int */
    protected $lower;
    
    /** @var float|int */
    protected $higher;
    
    /**
     * @param float|int $lower
     * @param float|int $higher
     */
    abstract protected static function create(int $mode, $lower, $higher): Filter;
    
    /**
     * @param float|int $lower
     * @param float|int $higher
     */
    final protected function __construct(int $mode, $lower, $higher)
    {
        parent::__construct($mode);
        
        if (\is_int($lower) || \is_float($lower)) {
            $this->lower = $lower;
        } else {
            throw InvalidParamException::describe('lower', $lower);
        }
        
        if (\is_int($higher) || \is_float($higher)) {
            $this->higher = $higher;
        } else {
            throw InvalidParamException::describe('higher', $higher);
        }
        
        if ($lower > $higher) {
            throw FilterExceptionFactory::paramLowerCannotBeGreaterThanHigher();
        }
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode, $this->lower, $this->higher)
            : $this;
    }
}