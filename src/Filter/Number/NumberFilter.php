<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Number;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

abstract class NumberFilter implements Filter
{
    /** @var float|int */
    protected $value;
    
    /**
     * @param float|int $value
     */
    public function __construct($value)
    {
        if (\is_int($value) || \is_float($value)) {
            $this->value = $value;
        } else {
            throw new \InvalidArgumentException('Invalid param value');
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
    
    abstract protected function test($value): bool;
}