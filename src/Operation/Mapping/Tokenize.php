<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Generator\Tokenizer;
use FiiSoft\Jackdaw\Producer\Producers;

final class Tokenize extends BaseOperation
{
    private Tokenizer $tokenizer;
    
    public function __construct(string $tokens)
    {
        $this->tokenizer = Producers::tokenizer($tokens);
        $this->tokenizer->keepIndex();
    }
    
    public function handle(Signal $signal): void
    {
        $signal->continueWith($this->tokenizer->restartWith($signal->item->value), $this->next);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            yield from $this->tokenizer->restartWith($value);
        }
    }
    
    protected function __clone()
    {
        $this->tokenizer = clone $this->tokenizer;
        
        parent::__clone();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->tokenizer->destroy();
            
            parent::destroy();
        }
    }
}