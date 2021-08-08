<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\Adapter;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class PredicateAdapter implements Discriminator
{
    private Predicate $predicate;
    
    public function __construct(Predicate $predicate)
    {
        $this->predicate = $predicate;
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key)
    {
        return $this->predicate->isSatisfiedBy($value, $key);
    }
}