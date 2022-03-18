<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Producer\Generator\Tokenizer;
use FiiSoft\Jackdaw\Producer\Producers;

final class Tokenize extends BaseOperation
{
    private Tokenizer $tokenizer;
    
    public function __construct(string $tokens)
    {
        $this->tokenizer = Producers::tokenizer($tokens, '');
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        if (\is_string($item->value)) {
            $this->tokenizer->restartWith($item->value);
            
            $signal->continueWith($this->tokenizer, $this->next);
        } else {
            throw new \RuntimeException(
                'Operation tokenize requires string value, but got '.Helper::typeOfParam($item->value)
            );
        }
    }
}