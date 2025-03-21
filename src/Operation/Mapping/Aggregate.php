<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Mapping\Aggregate\FullAggregate;
use FiiSoft\Jackdaw\Operation\Mapping\Aggregate\SingleAggregate;

abstract class Aggregate extends BaseOperation
{
    /** @var array<string|int> */
    protected array $keys;
    
    protected int $index = -1;
    
    /**
     * @param array<string|int> $keys
     */
    final public static function create(array $keys): self
    {
        if (self::isParamKeysValid($keys)) {
            $keys = \array_flip($keys);
            
            return \count($keys) === 1
                ? new SingleAggregate($keys)
                : new FullAggregate($keys);
        }
        
        throw InvalidParamException::describe('keys', $keys);
    }
    
    /**
     * @param array<string|int> $keys
     */
    private static function isParamKeysValid(array $keys): bool
    {
        if (empty($keys)) {
            return false;
        }
        
        foreach ($keys as $key) {
            if (\is_string($key)) {
                if ($key === '') {
                    return false;
                }
            } elseif (!\is_int($key)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * @param array<string|int> $keys
     */
    protected function __construct(array $keys)
    {
        $this->keys = $keys;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->keys = [];
            
            parent::destroy();
        }
    }
}