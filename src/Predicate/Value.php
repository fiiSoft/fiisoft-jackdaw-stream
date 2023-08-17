<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Predicate;

use FiiSoft\Jackdaw\Internal\Check;

final class Value implements Predicate
{
    /** @var mixed */
    private $value;
    
    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
    
    /**
     * @inheritDoc
     */
    public function isSatisfiedBy($value, $key = null, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return $value === $this->value;
            case Check::KEY:
                return $key === $this->value;
            case Check::BOTH:
                return $value === $this->value && $key === $this->value;
            case Check::ANY:
                return $value === $this->value || $key === $this->value;
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}