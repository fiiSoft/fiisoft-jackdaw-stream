<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

abstract class BaseFilter extends AbstractFilter
{
    protected int $mode;
    
    protected function __construct(?int $mode)
    {
        parent::__construct();
        
        $this->mode = Check::getMode($mode);
    }
    
    final public function getMode(): int
    {
        return $this->mode;
    }
}