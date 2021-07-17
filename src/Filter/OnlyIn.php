<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class OnlyIn implements Filter
{
    /** @var array|int[]|string[] */
    private $values;
    
    /** @var bool */
    private $hashMap;
    
    public function __construct(array $values)
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Invalid param values');
        }
        
        $this->hashMap = $this->canBeHashMapped($values);
    
        if ($this->hashMap) {
            $this->values = \array_flip($values);
        } else {
            $this->values = $values;
        }
    }
    
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        if ($this->hashMap) {
            switch ($mode) {
                case Check::VALUE:
                    return isset($this->values[$value]);
                case Check::KEY:
                    return isset($this->values[$key]);
                case Check::BOTH:
                    return isset($this->values[$value], $this->values[$key]);
                case Check::ANY:
                    return isset($this->values[$value]) || isset($this->values[$key]);
                default:
                    throw new \InvalidArgumentException('Invalid param mode');
            }
        }
    
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
    
    private function canBeHashMapped(array $values): bool
    {
        foreach ($values as $value) {
            if (!\is_int($value) && !\is_string($value)) {
                return false;
            }
        }
        
        return true;
    }
}