<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Remap extends StateMapper
{
    /** @var array<string|int> */
    private array $keys;
    
    /**
     * @param array<string|int> $keys
     */
    public function __construct(array $keys)
    {
        if (!$this->isParamKeysValid($keys)) {
            throw InvalidParamException::describe('keys', $keys);
        }
        
        $this->keys = $keys;
    }
    
    /**
     * @param array<string|int> $keys
     */
    private function isParamKeysValid(array $keys): bool
    {
        if (empty($keys)) {
            return false;
        }
    
        foreach ($keys as $before => $after) {
            if (!Helper::isFieldValid($before) || !Helper::isFieldValid($after)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        foreach ($this->keys as $before => $after) {
            $value[$after] = $value[$before];
            unset($value[$before]);
        }
        
        return $value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->keys as $before => $after) {
                $value[$after] = $value[$before];
                unset($value[$before]);
            }
            
            yield $key => $value;
        }
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