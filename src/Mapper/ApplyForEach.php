<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class ApplyForEach extends StateMapper
{
    private Mapper $mapper;
    
    /**
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function __construct($mapper)
    {
        $this->mapper = Mappers::getAdapter($mapper);
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        foreach ($value as $k => $v) {
            $value[$k] = $this->mapper->map($v, $k);
        }
        
        return $value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->mapper->map($v, $k);
            }
            
            yield $key => $value;
        }
    }
    
    public function equals(Mapper $other): bool
    {
        return $other instanceof $this && $other->mapper->equals($this->mapper);
    }
}