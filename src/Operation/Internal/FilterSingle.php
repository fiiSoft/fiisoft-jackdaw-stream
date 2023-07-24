<?php

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Filter\Internal\FilterData;

interface FilterSingle
{
    public function filterData(): FilterData;
}