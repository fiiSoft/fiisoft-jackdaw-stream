<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\Zip;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Mapping\Zip;

final class ZeroSizeZip extends Zip
{
    public function handle(Signal $signal): void
    {
        $signal->item->value = [$signal->item->value];
        
        $this->next->handle($signal);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => [$value];
        }
    }
}