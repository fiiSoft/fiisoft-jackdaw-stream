<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Time;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterFactory;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Filter\Time\Compare\Point\After;
use FiiSoft\Jackdaw\Filter\Time\Compare\Point\Before;
use FiiSoft\Jackdaw\Filter\Time\Compare\Point\From;
use FiiSoft\Jackdaw\Filter\Time\Compare\Point\Is;
use FiiSoft\Jackdaw\Filter\Time\Compare\Point\IsNot;
use FiiSoft\Jackdaw\Filter\Time\Compare\Point\Until;
use FiiSoft\Jackdaw\Filter\Time\Compare\Range\Between;
use FiiSoft\Jackdaw\Filter\Time\Compare\Range\Inside;
use FiiSoft\Jackdaw\Filter\Time\Compare\Range\NotInside;
use FiiSoft\Jackdaw\Filter\Time\Compare\Range\Outside;
use FiiSoft\Jackdaw\Filter\Time\Compare\Set\InSet;
use FiiSoft\Jackdaw\Filter\Time\Compare\Set\NotInSet;

final class TimeFilterFactory extends FilterFactory
{
    public static function instance(?int $mode = null): self
    {
        return new self($mode);
    }
    
    public function isDateTime(): Filter
    {
        return $this->get(Filters::isDateTime($this->mode));
    }
    
    /**
     * @param \DateTimeInterface|string $time
     */
    public function is($time): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new Is($time)));
    }
    
    /**
     * @param \DateTimeInterface|string $time
     */
    public function isNot($time): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new IsNot($time)));
    }
    
    /**
     * $x < $time
     *
     * @param \DateTimeInterface|string $time
     */
    public function before($time): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new Before($time)));
    }
    
    /**
     * $x <= $time
     *
     * @param \DateTimeInterface|string $time
     */
    public function until($time): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new Until($time)));
    }
    
    /**
     * $x > $time
     *
     * @param \DateTimeInterface|string $time
     */
    public function after($time): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new After($time)));
    }
    
    /**
     * $x >= $time
     *
     * @param \DateTimeInterface|string $time
     */
    public function from($time): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new From($time)));
    }
    
    /**
     * $x >= $earlier AND $x <= $later
     *
     * @param \DateTimeInterface|string $earlier
     * @param \DateTimeInterface|string $later
     */
    public function between($earlier, $later): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new Between($earlier, $later)));
    }
    
    /**
     * $x < $earlier OR $x > $later
     *
     * @param \DateTimeInterface|string $earlier
     * @param \DateTimeInterface|string $later
     */
    public function outside($earlier, $later): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new Outside($earlier, $later)));
    }
    
    /**
     * $x > $earlier AND $x < $later
     *
     * @param \DateTimeInterface|string $earlier
     * @param \DateTimeInterface|string $later
     */
    public function inside($earlier, $later): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new Inside($earlier, $later)));
    }
    
    /**
     * $x <= $earlier OR $x >= $later
     *
     * @param \DateTimeInterface|string $earlier
     * @param \DateTimeInterface|string $later
     */
    public function notInside($earlier, $later): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new NotInside($earlier, $later)));
    }
    
    /**
     * @param array<\DateTimeInterface|string> $dates
     */
    public function inSet(array $dates): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new InSet($dates)));
    }
    
    /**
     * @param array<\DateTimeInterface|string> $dates
     */
    public function notInSet(array $dates): Filter
    {
        return $this->get(TimeFilter::create($this->mode, new NotInSet($dates)));
    }
}