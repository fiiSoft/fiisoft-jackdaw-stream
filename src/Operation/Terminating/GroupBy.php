<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\StreamCollection;
use FiiSoft\Jackdaw\Operation\Internal\GroupingOperation;

final class GroupBy extends GroupingOperation
{
    public function result(): StreamCollection
    {
        return new StreamCollection($this, $this->collections);
    }
}