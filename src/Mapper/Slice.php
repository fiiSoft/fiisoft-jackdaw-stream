<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Exception\InvalidParamException;

final class Slice extends Internal\StateMapper
{
    private int $offset;
    private ?int $length;
    private bool $reindex;
    
    public function __construct(int $offset, ?int $length = null, bool $reindex = false)
    {
        if ($offset < 0) {
            throw InvalidParamException::describe('offset', $offset);
        }
        
        if ($length !== null && $length < 1) {
            throw InvalidParamException::describe('length', $length);
        }
        
        $this->length = $length;
        $this->offset = $offset;
        $this->reindex = $reindex;
    }
    
    /**
     * @return array<string|int, mixed>
     */
    public function map($value, $key = null): array
    {
        return $this->reindex
            ? \array_values(\array_slice($value, $this->offset, $this->length))
            : \array_slice($value, $this->offset, $this->length, true);
    }
    
    /**
     * @inheritDoc
     */
    protected function buildValueMapper(iterable $stream): iterable
    {
        if ($this->reindex) {
            foreach ($stream as $key => $value) {
                yield $key => \array_values(\array_slice($value, $this->offset, $this->length));
            }
        } else {
            foreach ($stream as $key => $value) {
                yield $key => \array_slice($value, $this->offset, $this->length, true);
            }
        }
    }
    
    /**
     * @inheritDoc
     */
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self) {
            if ($other->reindex) {
                $this->reindex = true;
            } elseif ($this->reindex && $other->offset > 0) {
                return false;
            }
            
            $this->offset += $other->offset;
            
            $otherLength = $other->length ?? \PHP_INT_MAX;
            $myLength = $this->length ?? \PHP_INT_MAX;
            
            if ($otherLength < $myLength) {
                $this->length = $otherLength;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * @inheritDoc
     */
    public function equals(Mapper $other): bool
    {
        return $other instanceof $this
            && $other->offset === $this->offset
            && $other->length === $this->length
            && $other->reindex === $this->reindex;
    }
    
    public function reindex(): void
    {
        $this->reindex = true;
    }
}