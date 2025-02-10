<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

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
}