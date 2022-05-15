<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Producers;

final class Gather extends BaseOperation
{
    private array $data = [];
    private bool $preserveKeys;
    
    public function __construct(bool $preserveKeys = false)
    {
        $this->preserveKeys = $preserveKeys;
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->preserveKeys) {
            $this->data[$signal->item->key] = $signal->item->value;
        } else {
            $this->data[] = $signal->item->value;
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if (!empty($this->data)) {
            
            $data = $this->data;
            $this->data = [];
            
            $signal->restartWith(Producers::fromArray([$data]), $this->next);
            
            return true;
        }
        
        return parent::streamingFinished($signal);
    }
    
    public function preserveKeys(): bool
    {
        return $this->preserveKeys;
    }
    
    public function reindexKeys(): void
    {
        $this->preserveKeys = false;
    }
}