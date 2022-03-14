<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Transformer\Adapter;

use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Transformer\Transformer;

final class MapperAdapter implements Transformer
{
    private Mapper $mapper;
    
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }
    
    /**
     * @inheritDoc
     */
    public function transform($value, $key)
    {
        return $this->mapper->map($value, $key);
    }
}