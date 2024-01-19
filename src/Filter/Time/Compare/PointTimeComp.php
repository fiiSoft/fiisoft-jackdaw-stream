<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time\Compare;

abstract class PointTimeComp extends TimeComparator
{
    protected \DateTimeInterface $time;
    
    /**
     * @param \DateTimeInterface|string $time
     */
    final public function __construct($time)
    {
        $this->time = $this->prepare($time);
    }
}