<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating\HasOnly;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly;

final class ValueHasOnly extends HasOnly
{
    public function handle(Signal $signal): void
    {
        if (!\in_array($signal->item->value, $this->values, true)) {
            $this->hasOnly = false;
            $signal->stop();
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            if (!\in_array($value, $this->values, true)) {
                $this->hasOnly = false;
                break;
            }
        }
        
        yield;
    }
}