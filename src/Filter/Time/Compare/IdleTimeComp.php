<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time\Compare;

use FiiSoft\Jackdaw\Filter\Exception\FilterExceptionFactory;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;

final class IdleTimeComp extends TimeComparator
{
    private Filter $isDateTime;
    
    private bool $result;
    
    public static function true(): self
    {
        return new self(true);
    }
    
    public static function false(): self
    {
        return new self(false);
    }
    
    private function __construct(bool $result, ?Filter $isDateTime = null)
    {
        $this->result = $result;
        $this->isDateTime = $isDateTime ?? Filters::isDateTime();
    }
    
    /**
     * @inheritDoc
     */
    public function isSatisfiedBy($time): bool
    {
        if ($this->isDateTime->isAllowed($time)) {
            return $this->result;
        }
        
        throw FilterExceptionFactory::invalidTimeValue($time);
    }
    
    public function negation(): TimeComparator
    {
        return new self(!$this->result, $this->isDateTime);
    }
}