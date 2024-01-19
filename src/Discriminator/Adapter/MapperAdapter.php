<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\Adapter;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Mapper\Mapper;

final class MapperAdapter implements Discriminator
{
    private Mapper $mapper;
    
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null)
    {
        return $this->mapper->map($value, $key);
    }
}