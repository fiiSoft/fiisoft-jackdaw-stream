<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator\Adapter;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Discriminator\Discriminator;

final class ConditionAdapter implements Discriminator
{
    private Condition $condition;
    
    public function __construct(Condition $condition)
    {
        $this->condition = $condition;
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key)
    {
        return $this->condition->isTrueFor($value, $key);
    }
}