<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating\HasOnly;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly;

final class KeyHasOnly extends HasOnly
{
    public function handle(Signal $signal): void
    {
        if (!\in_array($signal->item->key, $this->values, true)) {
            $this->hasOnly = false;
            $signal->stop();
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $_) {
            if (!\in_array($key, $this->values, true)) {
                $this->hasOnly = false;
                break;
            }
        }
        
        yield;
    }
}