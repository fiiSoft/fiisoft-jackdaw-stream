<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

abstract class StringFilter implements Filter
{
    protected string $value;
    protected int $length;
    protected bool $ignoreCase;
    
    public function __construct(string $value, bool $ignoreCase = false)
    {
        $this->value = $value;
        $this->length = \mb_strlen($value);
        $this->ignoreCase = $ignoreCase;
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
    
    abstract protected function test(string $value): bool;
}