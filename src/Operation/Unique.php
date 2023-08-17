<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Comparator\GenericComparator;
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
    
    private ?Comparator $comparator;
    private int $mode;
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public function __construct($comparator = null, int $mode = Check::VALUE)
    {
        $this->comparator = Comparators::getAdapter($comparator);
        $this->mode = Check::getMode($mode);
        
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
        if ($this->comparator !== null) {
            if ($this->comparator instanceof GenericComparator && $this->comparator->isFullAssoc()) {
                $this->checker = new FullAssocChecker($this->comparator);
                $this->mode = Check::BOTH;
                return;
            }
            
            $strategy = new ComparisonStrategy\CustomComparator($this->comparator);
        } else {
            $strategy = new ComparisonStrategy\StandardComparator();
        }
        
        switch ($this->mode) {
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
    
    public function comparator(): ?Comparator
    {
        return $this->comparator;
    }
    
    public function mode(): int
    {
        return $this->mode;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->checker->destroy();
            
            parent::destroy();
        }
    }
}