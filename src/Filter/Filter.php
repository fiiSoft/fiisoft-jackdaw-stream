<?php

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Condition\ConditionReady;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Mapper\MapperReady;

interface Filter extends MapperReady, ConditionReady, DiscriminatorReady
{
    /**
     * @param mixed $value
     * @param string|int $key
     * @param int $mode
     * @return bool
     */
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool;
}