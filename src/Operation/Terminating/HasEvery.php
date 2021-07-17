<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Stream;

final class HasEvery extends FinalOperation implements ResultProvider
{
    /** @var array */
    private $values;
    
    /** @var bool */
    private $hasEvery = false;
    
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
        $pos = \array_search($search, $this->values, true);
        if ($pos !== false) {
            unset($this->values[$pos]);
            if (empty($this->values)) {
                $this->hasEvery = true;
                $signal->stop();
            } else {
                return false;
            }
        }
        
        return true;
    }
    
    private function testValueAndKey(Signal $signal)
    {
        $item = $signal->item;
        
        $valPos = \array_search($item->value, $this->values, true);
        if ($valPos !== false) {
            $keyPos = \array_search($item->key, $this->values, true);
            if ($keyPos !== false) {
                unset($this->values[$valPos], $this->values[$keyPos]);
                if (empty($this->values)) {
                    $this->hasEvery = true;
                    $signal->stop();
                }
            }
        }
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->hasEvery);
    }
}