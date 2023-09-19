<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\Adapter;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Registry\RegReader;

final class RegistryAdapter implements Discriminator
{
    private RegReader $registry;
    
    public function __construct(RegReader $registry)
    {
        $this->registry = $registry;
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key)
    {
        return $this->registry->read();
    }
}