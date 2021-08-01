<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class OnlyWith implements Filter
{
    private array $keys;
    
    private bool $allowNulls;
    
    /**
     * @param array|string|int $keys
     * @param bool $allowNulls
     */
    public function __construct($keys, bool $allowNulls = false)
    {
        if (\is_array($keys)) {
            if (empty($keys)) {
                throw new \InvalidArgumentException('Param keys cannot be empty');
            }
        } elseif (\is_string($keys) || \is_int($keys)) {
            $keys = [$keys];
        } else {
            throw new \InvalidArgumentException('Invalid param keys');
        }
        
        $this->keys = $keys;
        $this->allowNulls = $allowNulls;
    }
    
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        if ($this->allowNulls) {
            if (\is_array($value)) {
                foreach ($this->keys as $k) {
                    if (!\array_key_exists($k, $value)) {
                        return false;
                    }
                }
                
                return true;
            }
    
            if ($value instanceof \ArrayAccess) {
                foreach ($this->keys as $k) {
                    if (!$value->offsetExists($k)) {
                        return false;
                    }
                }
                
                return true;
            }
        } elseif (\is_array($value) || $value instanceof \ArrayAccess) {
            foreach ($this->keys as $k) {
                if (!isset($value[$k])) {
                    return false;
                }
            }
            
            return true;
        }
        
        return false;
    }
}