<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Adapter;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Exception\ImpossibleSituationException;

final class DiscriminatorAdapter extends BaseAdapter
{
    private Discriminator $discriminator;
    
    public function __construct(Discriminator $discriminator)
    {
        $this->discriminator = $discriminator;
    }
    
    /**
     * @inheritDoc
     */
    public function compare($value1, $value2): int
    {
        return $this->discriminator->classify($value1) <=> $this->discriminator->classify($value2);
    }
    
    /**
     * @inheritDoc
     */
    public function compareAssoc($value1, $value2, $key1, $key2): int
    {
        throw ImpossibleSituationException::called(__METHOD__);
    }
}