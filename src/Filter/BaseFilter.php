<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Mode;

abstract class BaseFilter extends AbstractFilter
{
    protected int $mode;
    
    protected function __construct(?int $mode)
    {
        parent::__construct();
        
        $this->mode = Mode::get($mode);
    }
    
    final public function getMode(): int
    {
        return $this->mode;
    }
}