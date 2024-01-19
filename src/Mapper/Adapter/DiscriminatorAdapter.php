<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Adapter;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class DiscriminatorAdapter extends StateMapper
{
    private Discriminator $discriminator;
    
    public function __construct(Discriminator $discriminator)
    {
        $this->discriminator = $discriminator;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return $this->discriminator->classify($value, $key);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => $this->discriminator->classify($value, $key);
        }
    }
    
    protected function buildKeyMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $this->discriminator->classify($value, $key) => $value;
        }
    }
}