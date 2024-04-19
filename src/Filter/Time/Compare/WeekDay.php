<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time\Compare;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Filter\Exception\FilterExceptionFactory;
use FiiSoft\Jackdaw\Filter\Time\Day;

final class WeekDay extends TimeComparator
{
    private bool $isDay;
    
    private array $days;
    
    /**
     * @param bool $isDay false value means "is not day"
     * @param array $days it MUST be array of Day::* constants
     */
    public function __construct(bool $isDay, array $days)
    {
        if (!$this->areDaysValid($days)) {
            throw InvalidParamException::describe('days', $days);
        }
        
        $this->isDay = $isDay;
        $this->days = \array_flip($days);
    }
    
    /**
     * @inheritDoc
     */
    public function isSatisfiedBy($time): bool
    {
        if ($time instanceof \DateTimeInterface) {
            $weekDay = $time->format('D');
        } elseif (\is_string($time)) {
            $weekDay = (new \DateTimeImmutable($time))->format('D');
        } else {
            throw FilterExceptionFactory::invalidTimeValue($time);
        }
        
        return $this->isDay ? isset($this->days[$weekDay]) : !isset($this->days[$weekDay]);
    }
    
    public function negation(): TimeComparator
    {
        return new self(!$this->isDay, \array_flip($this->days));
    }
    
    private function areDaysValid(array $days): bool
    {
        if (empty($days)) {
            return false;
        }
        
        $allowed = \array_flip([Day::MON, Day::TUE, Day::WED, Day::THU, Day::FRI, Day::SAT, Day::SUN]);
        
        foreach ($days as $day) {
            if (!isset($allowed[$day])) {
                return false;
            }
        }
        
        return true;
    }
}