<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class ByArgs extends StateMapper
{
    /** @var callable */
    private $mapper;
    
    public function __construct(callable $mapper)
    {
        $this->mapper = $mapper;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return ($this->mapper)(...\array_values($value));
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => ($this->mapper)(...\array_values($value));
        }
    }
}