<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\ComparisonStrategy;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\ItemByItemChecker\FullAssocChecker;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\ItemByItemChecker\PairChecker;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\UniquenessChecker;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Unique extends BaseOperation
{
    private UniquenessChecker $checker;
    private Comparison $comparison;
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public function __construct($comparison = null)
    {
        $this->comparison = Comparison::prepare($comparison);
    }
    
    public function prepare(): void
    {
        parent::prepare();
        
        $this->prepareStrategy();
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->checker->check($signal->item)) {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = new Item();
        
        foreach ($stream as $item->key => $item->value) {
            if ($this->checker->check($item)) {
                yield $item->key => $item->value;
            }
        }
    }
    
    private function prepareStrategy(): void
    {
        if ($this->comparison->isPairComparison()) {
            $this->checker = new PairChecker(...$this->comparison->getComparators());
            return;
        }
        
        $comparator = $this->comparison->comparator();
        
        if ($comparator !== null) {
            if ($comparator instanceof GenericComparator && $comparator->isFullAssoc()) {
                $this->checker = new FullAssocChecker($comparator);
                return;
            }
            
            $strategy = new ComparisonStrategy\CustomComparator($comparator);
        } else {
            $strategy = new ComparisonStrategy\StandardComparator();
        }
        
        switch ($this->comparison->mode()) {
            case Check::VALUE:
                $this->checker = new StandardChecker\Single\CheckValue($strategy);
            break;
            case Check::KEY:
                $this->checker = new StandardChecker\Single\CheckKey($strategy);
            break;
            case Check::BOTH:
                $this->checker = new StandardChecker\Double\CheckValueAndKey($strategy);
            break;
            default:
                $this->checker = new StandardChecker\Double\CheckValueOrKey($strategy);
        }
    }
    
    public function comparison(): Comparison
    {
        return $this->comparison;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->checker->destroy();
            
            parent::destroy();
        }
    }
}