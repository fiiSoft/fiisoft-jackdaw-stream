<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\Filter;

abstract class StringFilterSingle extends AbstractStringFilter
{
    protected string $value;
    protected int $length;
    
    abstract protected static function create(int $mode, string $value, bool $ignoreCase = false): self;
    
    final protected function __construct(int $mode, string $value, bool $ignoreCase)
    {
        parent::__construct($mode, $ignoreCase);
        
        $this->value = $value;
        $this->length = \mb_strlen($value);
    }
    
    final public function inMode(?int $mode): StringFilter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode, $this->value, $this->ignoreCase)
            : $this;
    }
    
    final public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->value === $this->value
            && $other->length === $this->length
            && parent::equals($other);
    }
}