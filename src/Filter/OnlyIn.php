<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class OnlyIn implements Filter
{
    private const VARIOUS = 0, INTS_MAP = 1, STRINGS_MAP = 2;
    
    private array $values;
    private int $workMode = self::VARIOUS;
    
    public function __construct(array $values)
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Invalid param values');
        }
        
        $this->determineWorkMode($values);
        $this->prepareValues($values);
    }
    
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        $mode = Check::getMode($mode);
        
        if ($this->workMode === self::STRINGS_MAP) {
            switch ($mode) {
                case Check::VALUE:
                    return \is_string($value) && isset($this->values[$value]);
                case Check::KEY:
                    return \is_string($key) && isset($this->values[$key]);
                case Check::BOTH:
                    return \is_string($key) && \is_string($value) && isset($this->values[$value], $this->values[$key]);
                default:
                    return \is_string($value) && isset($this->values[$value])
                        || \is_string($key) && isset($this->values[$key]);
            }
        }
        
        if ($this->workMode === self::INTS_MAP) {
            switch ($mode) {
                case Check::VALUE:
                    return \is_int($value) && isset($this->values[$value]);
                case Check::KEY:
                    return \is_int($key) && isset($this->values[$key]);
                case Check::BOTH:
                    return \is_int($key) && \is_int($value) && isset($this->values[$value], $this->values[$key]);
                default:
                    return \is_int($value) && isset($this->values[$value])
                        || \is_int($key) && isset($this->values[$key]);
            }
        }
        
        switch ($mode) {
            case Check::VALUE:
                return \in_array($value, $this->values, true);
            case Check::KEY:
                return \in_array($key, $this->values, true);
            case Check::BOTH:
                return \in_array($value, $this->values, true) && \in_array($key, $this->values, true);
            default:
                return \in_array($value, $this->values, true) || \in_array($key, $this->values, true);
        }
    }
    
    private function prepareValues(array $values): void
    {
        $this->values = $this->workMode === self::VARIOUS
            ? \array_unique($values, \SORT_REGULAR)
            : \array_flip($values);
    }
    
    private function determineWorkMode(array $values): void
    {
        $stringFound = $intFound = false;
        
        foreach ($values as $value) {
            if (\is_string($value)) {
                $stringFound = true;
                if ($intFound) {
                    return;
                }
            } elseif (\is_int($value)) {
                $intFound = true;
                if ($stringFound) {
                    return;
                }
            } else {
                return;
            }
        }
        
        $this->workMode = $stringFound ? self::STRINGS_MAP : self::INTS_MAP;
    }
}