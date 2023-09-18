<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Basic\GenericComparator;
use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\ComparisonStrategy;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\FullAssocChecker;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\StandardChecker;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\UniquenessChecker;

final class Unique extends BaseOperation
{
    private UniquenessChecker $checker;
    private Comparison $comparison;
    
    /**
     * @param Comparison|Comparable|callable|null $comparison
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
            case Check::ANY:
                $this->checker = new StandardChecker\CheckValueOrKey($strategy);
            break;
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