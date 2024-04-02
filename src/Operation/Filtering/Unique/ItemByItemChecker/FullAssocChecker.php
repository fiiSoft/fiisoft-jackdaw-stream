<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering\Unique\ItemByItemChecker;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Operation\Exception\OperationExceptionFactory;

final class FullAssocChecker extends QuickChecker
{
    /** @var callable */
    private $comparator;
    
    public function __construct(GenericComparator $comparator)
    {
        if ($comparator->isFullAssoc()) {
            $this->comparator = $comparator->getWrappedCallable();
        } else {
            throw OperationExceptionFactory::invalidComparator();
        }
    }
    
    protected function compare(Item $first, Item $second): int
    {
        return ($this->comparator)($first->value, $second->value, $first->key, $second->key);
    }
}