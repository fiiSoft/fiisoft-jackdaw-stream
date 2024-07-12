<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\Mappers;

final class MultiMapper extends StateMapper
{
    /** @var Mapper[]  */
    private array $pattern = [];
    
    /**
     * @param array<string|int, mixed> $pattern
     */
    public function __construct(array $pattern)
    {
        if (empty($pattern)) {
            throw InvalidParamException::byName('pattern');
        }
        
        $this->pattern = \array_map(static fn($item): Mapper => Mappers::getAdapter($item), $pattern);
    }
    
    /**
     * @param mixed $value
     * @param mixed $key
     * @return array<string|int, mixed>
     */
    public function map($value, $key = null): array
    {
        return \array_map(static fn(Mapper $mapper) => $mapper->map($value, $key), $this->pattern);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => \array_map(static fn(Mapper $mapper) => $mapper->map($value, $key), $this->pattern);
        }
    }
}