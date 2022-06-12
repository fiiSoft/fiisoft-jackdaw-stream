<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\Adapter;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class PredicateAdapter implements Discriminator
{
    private Predicate $predicate;
    
    private int $mode;
    
    public function __construct(Predicate $predicate, int $mode = Check::VALUE)
    {
        $this->predicate = $predicate;
        $this->mode = Check::getMode($mode);
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key)
    {
        return $this->predicate->isSatisfiedBy($value, $key, $this->mode);
    }
}