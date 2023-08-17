<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Mapper\Mappers;

final class MultiMapper extends BaseMapper
{
    /** @var Mapper[]  */
    private array $pattern = [];
    
    public function __construct(array $pattern)
    {
        if (empty($pattern)) {
            throw new \InvalidArgumentException('Invalid param pattern - cannot be empty!');
        }
        
        $this->pattern = \array_map(static fn($item): Mapper => Mappers::getAdapter($item), $pattern);
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key): array
    {
        return \array_map(static fn(Mapper $mapper) => $mapper->map($value, $key), $this->pattern);
    }
}