<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer\Internal;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Reducer\Reducer;

abstract class BaseReducer implements Reducer
{
    final public function getResult(): Item
    {
        return new Item(0, $this->result());
    }
}