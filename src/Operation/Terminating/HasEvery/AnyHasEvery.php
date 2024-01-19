<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating\HasEvery;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery;

final class AnyHasEvery extends HasEvery
{
    public function handle(Signal $signal): void
    {
        $pos = \array_search($signal->item->value, $this->values, true);
        if ($pos !== false) {
            unset($this->values[$pos]);
            
            if (empty($this->values)) {
                $this->hasEvery = true;
                $signal->stop();
                return;
            }
        }
        
        $pos = \array_search($signal->item->key, $this->values, true);
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
        foreach ($stream as $key => $value) {
            
            $pos = \array_search($value, $this->values, true);
            if ($pos !== false) {
                unset($this->values[$pos]);
                
                if (empty($this->values)) {
                    $this->hasEvery = true;
                    break;
                }
            }
            
            $pos = \array_search($key, $this->values, true);
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