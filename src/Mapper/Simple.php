<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Simple extends StateMapper
{
    /** @var mixed */
    private $value;
    
    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return $this->value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $_) {
            yield $key => $this->value;
        }
    }
    
    protected function buildKeyMapper(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            yield $this->value => $value;
        }
    }
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self) {
            $this->value = $other->value;
            return true;
        }
        
        return false;
    }
}