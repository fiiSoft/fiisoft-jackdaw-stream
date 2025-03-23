<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Mode;

abstract class BaseFilter extends AbstractFilter
{
    protected int $mode;
    
    protected function __construct(?int $mode)
    {
        $this->mode = Mode::get($mode);
    }
    
    final public function getMode(): int
    {
        return $this->mode;
    }
    
    public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this && $other->mode === $this->mode;
    }
}