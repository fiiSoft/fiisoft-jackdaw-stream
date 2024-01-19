<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyWith;

final class OnlyWithAllowNulls extends OnlyWith
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        if (\is_array($value)) {
            foreach ($this->fields as $k) {
                if (!\array_key_exists($k, $value)) {
                    return false;
                }
            }
            
            return true;
        }
        
        if ($value instanceof \ArrayAccess) {
            foreach ($this->fields as $k) {
                if (!$value->offsetExists($k)) {
                    return false;
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_array($value)) {
                foreach ($this->fields as $k) {
                    if (\array_key_exists($k, $value)) {
                        continue;
                    }
                    continue 2;
                }
                
                yield $key => $value;
            } elseif ($value instanceof \ArrayAccess) {
                foreach ($this->fields as $k) {
                    if ($value->offsetExists($k)) {
                        continue;
                    }
                    continue 2;
                }
                
                yield $key => $value;
            }
        }
    }
}