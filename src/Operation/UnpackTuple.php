<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class UnpackTuple extends BaseOperation
{
    private bool $assoc;
    
    public function __construct(bool $assoc = false)
    {
        $this->assoc = $assoc;
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if (\is_array($item->value) && \count($item->value) === 2) {
            
            if ($this->assoc) {
                $item->key = $item->value['key'];
                $item->value = $item->value['value'];
            } else {
                $item->key = $item->value[0];
                $item->value = $item->value[1];
            }
            
            $this->next->handle($signal);
        } else {
            throw new \RuntimeException('UnpackTuple cannot handle value which is not a valid tuple');
        }
    }
    
    public function isAssoc(): bool
    {
        return $this->assoc;
    }
}