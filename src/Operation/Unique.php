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
    
    /**
     * @param Comparator|callable|null $comparator
     */
    public function __construct($comparator = null, int $mode = Check::VALUE)
    {
        $this->choseStrategy(Check::getMode($mode), Comparators::getAdapter($comparator));
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->strategy->check($signal->item)) {
            $this->next->handle($signal);
        }
    }
    
    private function choseStrategy(int $mode, ?Comparator $comparator = null): void
    {
        switch ($mode) {
            case Check::VALUE:
                $this->strategy = $comparator !== null
                    ? new ValueComparator($comparator)
                    : new ValueStandard();
            break;
            case Check::KEY:
                $this->strategy = $comparator !== null
                    ? new KeyComparator($comparator)
                    : new KeyStandard();
            break;
            case Check::BOTH:
                $this->strategy = $comparator !== null
                    ? new ValueAndKeyComparator($comparator)
                    : new ValueAndKeyStandard();
            break;
            case Check::ANY:
                $this->strategy = $comparator !== null
                    ? new ValueOrKeyComparator($comparator)
                    : new ValueOrKeyStandard();
            break;
        }
    }
}