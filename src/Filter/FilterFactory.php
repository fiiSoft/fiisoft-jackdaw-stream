<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Mode;

abstract class FilterFactory
{
    protected int $mode;
    protected bool $isNot;
    
    /** @var array<string, static> */
    private static array $factories = [];
    
    /**
     * @return static
     */
    final public static function instance(?int $mode = null, bool $isNot = false)
    {
        $mode = Mode::get($mode);
        $key = static::class.'_'.$mode.'_'.($isNot ? '1' : '0');
        
        if (!isset(self::$factories[$key])) {
            self::$factories[$key] = new static($mode, $isNot);
        }
        
        return self::$factories[$key];
    }
    
    final protected function __construct(int $mode, bool $isNot)
    {
        $this->mode = $mode;
        $this->isNot = $isNot;
    }
    
    /**
     * @return static
     */
    final protected function negate()
    {
        return static::instance($this->mode, !$this->isNot);
    }
    
    final protected function get(Filter $filter): Filter
    {
        return $this->isNot ? $filter->negate() : $filter;
    }
}