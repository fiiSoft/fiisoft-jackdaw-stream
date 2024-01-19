<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Simple;

use FiiSoft\Jackdaw\Filter\BaseFilter;
use FiiSoft\Jackdaw\Filter\Filter;

abstract class SimpleFilter extends BaseFilter
{
    /** @var mixed */
    protected $desired;
    
    /**
     * @param mixed $desired
     */
    abstract protected static function create(?int $mode, $desired): self;
    
    /**
     * @param mixed $desired
     */
    final protected function __construct(int $mode, $desired)
    {
        parent::__construct($mode);
        
        $this->desired = $desired;
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode, $this->desired)
            : $this;
    }
}