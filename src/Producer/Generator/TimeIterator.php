<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Producer\Generator\Exception\GeneratorExceptionFactory;
use FiiSoft\Jackdaw\Producer\Tech\LimitedProducer;

final class TimeIterator extends LimitedProducer
{
    private \DateTimeImmutable $startDate;
    private ?\DateTimeImmutable $endDate = null;
    private \DateInterval $interval;
    private bool $decrement = false;
    
    /**
     * @param \DateTimeInterface|string|int|null $startDate null value means "now"
     * @param \DateInterval|string|null $interval null value means "1 day"
     * @param \DateTimeInterface|string|int|null $endDate null value means - no end date
     */
    public function __construct(
        $startDate = null,
        $interval = null,
        $endDate = null,
        ?int $limit = null
    ) {
        parent::__construct($limit ?? \PHP_INT_MAX);
        
        $this->startDate = $this->parseDateTime('startDate', $startDate);
        
        if ($endDate !== null) {
            $this->endDate = $this->parseDateTime('endDate', $endDate);
        }
        
        $this->interval = $this->parseInterval($interval);
        
        if ($this->interval->invert === 1){
            $this->decrement = true;
            $this->interval->invert = 0;
        } elseif ($this->endDate !== null && $this->endDate < $this->startDate) {
            $this->decrement = true;
        }
    }
    
    /**
     * @inheritDoc
     */
    public function getIterator(): \Generator
    {
        $count = -1;
        $limit = $this->limit - 1;
        $current = $this->startDate;
        
        if ($this->endDate !== null) {
            if ($this->decrement) {
                if ($count < $limit && $current >= $this->endDate) {
                    yield ++$count => $current;
                    
                    while ($count < $limit) {
                        $current = $current->sub($this->interval);
                        
                        if ($current < $this->endDate) {
                            break;
                        }
                        
                        yield ++$count => $current;
                    }
                }
            } elseif ($count < $limit && $current <= $this->endDate) {
                yield ++$count => $current;
                
                while ($count < $limit) {
                    $current = $current->add($this->interval);
                    
                    if ($current > $this->endDate) {
                        break;
                    }
                    
                    yield ++$count => $current;
                }
            }
        } elseif ($this->decrement) {
            if ($count < $limit) {
                yield ++$count => $current;
                
                while ($count < $limit) {
                    $current = $current->sub($this->interval);
                    
                    yield ++$count => $current;
                }
            }
        } elseif ($count < $limit) {
            yield ++$count => $current;
            
            while ($count < $limit) {
                $current = $current->add($this->interval);
                
                yield ++$count => $current;
            }
        }
    }
    
    /**
     * @param \DateTimeInterface|string|int|null $dateTime
     */
    private function parseDateTime(string $name, $dateTime): \DateTimeImmutable
    {
        if ($dateTime === null) {
            return new \DateTimeImmutable();
        }
        
        if ($dateTime instanceof \DateTimeImmutable) {
            return $dateTime;
        }
        
        if ($dateTime instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($dateTime);
        }
        
        if (\is_string($dateTime) && $dateTime !== '') {
            return new \DateTimeImmutable($dateTime);
        }
        
        if (\is_int($dateTime)) {
            return new \DateTimeImmutable('@'.$dateTime);
        }
        
        throw GeneratorExceptionFactory::invalidDateTimeParam($name, $dateTime);
    }
    
    /**
     * @param \DateInterval|string|null $interval
     */
    private function parseInterval($interval): \DateInterval
    {
        if ($interval === null) {
            return new \DateInterval('P1D');
        }
        
        if ($interval instanceof \DateInterval) {
            return $interval;
        }
        
        if (\is_string($interval) && $interval !== '') {
            if (\strncmp($interval, '-', 1) === 0) {
                $interval = \mb_substr($interval, 1);
                $this->decrement = true;
            } else {
                $this->decrement = false;
            }
            
            $di = \DateInterval::createFromDateString($interval);
            
            if ($di !== false) {
                return $di;
            }
        }
        
        throw GeneratorExceptionFactory::invalidDateIntervalParam('interval', $interval);
    }
}