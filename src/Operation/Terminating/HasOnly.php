<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Stream;

final class HasOnly extends FinalOperation implements ResultProvider
{
    /** @var array */
    private $values;
    
    /** @var bool */
    private $hasOnly = true;
    
    /** @var int */
    private $mode;
    
    public function __construct(Stream $stream, array $values, int $mode = Check::VALUE)
    {
        $this->values = \array_unique($values, \SORT_REGULAR);
        $this->mode = Check::getMode($mode);
        
        parent::__construct($stream, $this);
    }
    
    public function handle(Signal $signal)
    {
        if ($this->mode === Check::VALUE) {
            $this->testSingle($signal->item->value, $signal);
        } elseif ($this->mode === Check::KEY) {
            $this->testSingle($signal->item->key, $signal);
        } elseif ($this->mode === Check::ANY) {
            $this->testSingle($signal->item->value, $signal) || $this->testSingle($signal->item->key, $signal);
        } else {
            $this->testValueAndKey($signal);
        }
    }
    
    private function testSingle($search, Signal $signal): bool
    {
        if (!\in_array($search, $this->values, true)) {
            $this->hasOnly = false;
            $signal->stop();
            return true;
        }
        
        return false;
    }
    
    private function testValueAndKey(Signal $signal)
    {
        if (!\in_array($signal->item->value, $this->values, true)
            || !\in_array($signal->item->key, $this->values, true)
        ) {
            $this->hasOnly = false;
            $signal->stop();
        }
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->hasOnly);
    }
}