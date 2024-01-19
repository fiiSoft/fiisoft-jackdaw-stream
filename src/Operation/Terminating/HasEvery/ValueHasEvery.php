<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating\HasEvery;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery;

final class ValueHasEvery extends HasEvery
{
    public function handle(Signal $signal): void
    {
        $pos = \array_search($signal->item->value, $this->values, true);
        if ($pos !== false) {
            unset($this->values[$pos]);
            
            if (empty($this->values)) {
                $this->hasEvery = true;
                $signal->stop();
            }
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            
            $pos = \array_search($value, $this->values, true);
            if ($pos !== false) {
                unset($this->values[$pos]);
                
                if (empty($this->values)) {
                    $this->hasEvery = true;
                    break;
                }
            }
        }
        
        yield;
    }
}