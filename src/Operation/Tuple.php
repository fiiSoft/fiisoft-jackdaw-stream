<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Tuple extends BaseOperation
{
    private bool $assoc;
    private int $index = 0;
    
    public function __construct(bool $assoc = false)
    {
        $this->assoc = $assoc;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->assoc) {
            $signal->item->value = ['key' => $signal->item->key, 'value' => $signal->item->value];
        } else {
            $signal->item->value = [$signal->item->key, $signal->item->value];
        }
        
        $signal->item->key = $this->index++;
        
        $this->next->handle($signal);
    }
    
    public function isAssoc(): bool
    {
        return $this->assoc;
    }
}