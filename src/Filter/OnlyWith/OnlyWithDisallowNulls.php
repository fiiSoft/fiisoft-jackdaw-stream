<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\OnlyWith;

final class OnlyWithDisallowNulls extends OnlyWith
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        foreach ($this->fields as $k) {
            if (!isset($value[$k])) {
                return false;
            }
        }
        
        return true;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->fields as $k) {
                if (isset($value[$k])) {
                    continue;
                }
                continue 2;
            }
            
            yield $key => $value;
        }
    }
}