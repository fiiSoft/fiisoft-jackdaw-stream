<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time\Compare;

abstract class SetTimeComp extends CompoundTimeComp
{
    /** @var \DateTimeInterface[] */
    protected array $dates = [];
    
    /**
     * @param array<\DateTimeInterface|string> $dates
     */
    final public function __construct(array $dates)
    {
        foreach ($dates as $date) {
            $date = $this->prepare($date);
            
            $this->dates[$date->format('c')] = $date;
        }
    }
}