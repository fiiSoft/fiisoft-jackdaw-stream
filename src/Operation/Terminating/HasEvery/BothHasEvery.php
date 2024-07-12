<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating\HasEvery;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery;
use FiiSoft\Jackdaw\Stream;

final class BothHasEvery extends HasEvery
{
    /** @var array<string|int, mixed> */
    private array $keys;
    
    /**
     * @inheritDoc
     */
    protected function __construct(Stream $stream, array $values)
    {
        parent::__construct($stream, $values);
        
        $this->keys = $values;
    }
    
    public function handle(Signal $signal): void
    {
        if (!empty($this->values)) {
            $valPos = \array_search($signal->item->value, $this->values, true);
            if ($valPos !== false) {
                unset($this->values[$valPos]);
            }
        }
        
        if (!empty($this->keys)) {
            $keyPos = \array_search($signal->item->key, $this->keys, true);
            if ($keyPos !== false) {
                unset($this->keys[$keyPos]);
            }
        }
        
        if (empty($this->values) && empty($this->keys)) {
            $this->hasEvery = true;
            $signal->stop();
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (!empty($this->values)) {
                $valPos = \array_search($value, $this->values, true);
                if ($valPos !== false) {
                    unset($this->values[$valPos]);
                }
            }
            
            if (!empty($this->keys)) {
                $keyPos = \array_search($key, $this->keys, true);
                if ($keyPos !== false) {
                    unset($this->keys[$keyPos]);
                }
            }
            
            if (empty($this->values) && empty($this->keys)) {
                $this->hasEvery = true;
                break;
            }
        }
        
        yield;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->keys = [];
            
            parent::destroy();
        }
    }
}