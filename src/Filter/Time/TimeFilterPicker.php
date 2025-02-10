<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time;

use FiiSoft\Jackdaw\Filter\Filter;

interface TimeFilterPicker
{
    public function isDateTime(): Filter;
    
    public function not(): TimeFilterPicker;
    
    /**
     * @param string ...$days at least one of Day::* constants
     */
    public function isDay(...$days): Filter;
    
    /**
     * @param string ...$days at least one of Day::* constants
     */
    public function isNotDay(...$days): Filter;
    
    /**
     * @param \DateTimeInterface|string $time
     */
    public function is($time): Filter;
    
    /**
     * @param \DateTimeInterface|string $time
     */
    public function isNot($time): Filter;
    
    /**
     * $x >= $time
     *
     * @param \DateTimeInterface|string $time
     */
    public function from($time): Filter;
    
    /**
     * $x <= $time
     *
     * @param \DateTimeInterface|string $time
     */
    public function until($time): Filter;
    
    /**
     * $x < $time
     *
     * @param \DateTimeInterface|string $time
     */
    public function before($time): Filter;
    
    /**
     * $x > $time
     *
     * @param \DateTimeInterface|string $time
     */
    public function after($time): Filter;
    
    /**
     * $x > $earlier AND $x < $later
     *
     * @param \DateTimeInterface|string $earlier
     * @param \DateTimeInterface|string $later
     */
    public function inside($earlier, $later): Filter;
    
    /**
     * $x <= $earlier OR $x >= $later
     *
     * @param \DateTimeInterface|string $earlier
     * @param \DateTimeInterface|string $later
     */
    public function notInside($earlier, $later): Filter;
    
    /**
     * $x >= $earlier AND $x <= $later
     *
     * @param \DateTimeInterface|string $earlier
     * @param \DateTimeInterface|string $later
     */
    public function between($earlier, $later): Filter;
    
    /**
     * $x < $earlier OR $x > $later
     *
     * @param \DateTimeInterface|string $earlier
     * @param \DateTimeInterface|string $later
     */
    public function outside($earlier, $later): Filter;
    
    /**
     * @param array<\DateTimeInterface|string> $dates
     */
    public function inSet(array $dates): Filter;
    
    /**
     * @param array<\DateTimeInterface|string> $dates
     */
    public function notInSet(array $dates): Filter;
}