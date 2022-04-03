<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Strategy\Unique;

use FiiSoft\Jackdaw\Internal\Item;

final class KeyStandard implements Strategy
{
    private array $keysMap = [];
    private array $keys = [];
    
    public function check(Item $item): bool
    {
        $key = $item->key;
        
        if (\is_int($key) || \is_string($key)) {
            if (isset($this->keysMap[$key])) {
                return false;
            }
            
            $this->keysMap[$key] = true;
            return true;
        }
        
        if (\in_array($key, $this->keys, true)) {
            return false;
        }
        
        $this->keys[] = $key;
        
        return true;
    }
}