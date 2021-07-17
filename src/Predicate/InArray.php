<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Predicate;

use FiiSoft\Jackdaw\Internal\Check;

final class InArray implements Predicate
{
    /** @var array */
    private $values;
    
    public function __construct(array $values)
    {
        $this->values = $values;
    }
    
    public function isSatisfiedBy($value, $key = null, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return \in_array($value, $this->values, true);
            case Check::KEY:
                return \in_array($key, $this->values, true);
            case Check::BOTH:
                return \in_array($value, $this->values, true) && \in_array($key, $this->values, true);
            case Check::ANY:
                return \in_array($value, $this->values, true) || \in_array($key, $this->values, true);
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}