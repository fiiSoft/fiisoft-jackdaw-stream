<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Comparators;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\KeyComparator;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\KeyStandard;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\Strategy;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\ValueAndKeyComparator;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\ValueAndKeyStandard;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\ValueComparator;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\ValueOrKeyComparator;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\ValueOrKeyStandard;
use FiiSoft\Jackdaw\Operation\Strategy\Unique\ValueStandard;

final class Unique extends BaseOperation
{
    private Strategy $strategy;
    
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
        if ($this->strategy->check($signal->item)) {
            $this->next->handle($signal);
        }
    }
    
    private function prepareStrategy(): void
    {
        switch ($this->mode) {
            case Check::VALUE:
                $this->strategy = $this->comparator !== null
                    ? new ValueComparator($this->comparator)
                    : new ValueStandard();
            break;
            case Check::KEY:
                $this->strategy = $this->comparator !== null
                    ? new KeyComparator($this->comparator)
                    : new KeyStandard();
            break;
            case Check::BOTH:
                $this->strategy = $this->comparator !== null
                    ? new ValueAndKeyComparator($this->comparator)
                    : new ValueAndKeyStandard();
            break;
            case Check::ANY:
                $this->strategy = $this->comparator !== null
                    ? new ValueOrKeyComparator($this->comparator)
                    : new ValueOrKeyStandard();
            break;
        }
    }
    
    protected function __clone()
    {
        $this->prepareStrategy();
        
        parent::__clone();
    }
}