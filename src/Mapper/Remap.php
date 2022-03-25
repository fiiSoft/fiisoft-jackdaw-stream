<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Remap extends BaseMapper
{
    private array $keys;
    
    public function __construct(array $keys)
    {
        if (empty($keys)) {
            throw new \InvalidArgumentException('Invalid param keys');
        }
    
        foreach ($keys as $before => $after) {
            if (!Helper::isFieldValid($before) || !Helper::isFieldValid($after)) {
                throw new \InvalidArgumentException('Invalid element in param keys');
            }
        }
        
        $this->keys = $keys;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_array($value) || $value instanceof \ArrayAccess) {
            
            foreach ($this->keys as $before => $after) {
                $value[$after] = $value[$before];
                unset($value[$before]);
            }
            
            return $value;
        }
    
        throw new \LogicException('Unable to remap keys in value which is not an array');
    }
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self) {
            foreach ($other->keys as $before => $after) {
                $pos = \array_search($before, $this->keys, true);
                if ($pos !== false) {
                    $this->keys[$pos] = $after;
                } else {
                    $this->keys[$before] = $after;
                }
            }
            
            return true;
        }
        
        return false;
    }
}