<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class Same implements Filter
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
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return $value === $this->value;
            case Check::KEY:
                return $key === $this->value;
            case Check::BOTH:
                return $key === $this->value && $value === $this->value;
            case Check::ANY:
                return $key === $this->value || $value === $this->value;
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
}