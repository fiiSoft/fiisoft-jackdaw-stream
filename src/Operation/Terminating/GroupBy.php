<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Collection\BaseStreamCollection;
use FiiSoft\Jackdaw\Operation\Internal\GroupingOperation;

final class GroupBy extends GroupingOperation
{
    public function result(): BaseStreamCollection
    {
        return BaseStreamCollection::create($this, $this->collections);
    }
}