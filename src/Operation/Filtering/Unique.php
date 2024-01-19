<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\ComparisonStrategy;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\FullAssocChecker;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\StandardChecker;
use FiiSoft\Jackdaw\Operation\Filtering\Unique\UniquenessChecker;

final class Unique extends BaseOperation
{
    private UniquenessChecker $checker;
    private Comparison $comparison;
    
    /**
     * @param Comparable|callable|null $comparison
     */
    public function __construct($comparison = null)
    {
        $this->comparison = Comparison::prepare($comparison);
        
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
                $this->checker = new StandardChecker\CheckValue($strategy);
            break;
            case Check::KEY:
                $this->checker = new StandardChecker\CheckKey($strategy);
            break;
            case Check::BOTH:
                $this->checker = new StandardChecker\CheckValueAndKey($strategy);
            break;
            default:
                $this->checker = new StandardChecker\CheckValueOrKey($strategy);
        }
    }
    
    protected function __clone()
    {
        $this->prepareStrategy();
        
        parent::__clone();
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