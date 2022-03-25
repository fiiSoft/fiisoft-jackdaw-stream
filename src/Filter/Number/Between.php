<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

final class Between implements Filter
{
    /** @var float|int */
    private $lower;
    
    /** @var float|int */
    private $higher;
    
    /**
     * @param float|int $lower
     * @param float|int $higher
     */
    public function __construct($lower, $higher)
    {
        if (\is_int($lower) || \is_float($lower)) {
            $this->lower = $lower;
        } else {
            throw new \InvalidArgumentException('Invalid param lower');
        }
        
        if (\is_int($higher) || \is_float($higher)) {
            $this->higher = $higher;
        } else {
            throw new \InvalidArgumentException('Invalid param higher');
        }
    
        if ($lower > $higher) {
            throw new \LogicException('Lower number is greater from higher number');
        }
    }
    
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return $this->test($value);
            case Check::KEY:
                return $this->test($key);
            case Check::BOTH:
                return $this->test($value) && $this->test($key);
            case Check::ANY:
                return $this->test($value) || $this->test($key);
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
    
    private function test($value): bool
    {
        if (\is_int($value) || \is_float($value)) {
            return $value >= $this->lower && $value <= $this->higher;
        }
    
        if (\is_numeric($value)) {
            $value = (float) $value;
            
            return $value >= $this->lower && $value <= $this->higher;
        }
    
        throw new \LogicException('Cannot compare value which is not a number');
    }
}