<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Producer;

final class Tokenizer implements Producer
{
    private string $string;
    private string $tokens;
    
    private int $index = 0;
    
    public function __construct(string $tokens, string $string = '')
    {
        $this->string = $string;
        $this->tokens = $tokens;
    }
    
    public function feed(Item $item): \Generator
    {
        $item->value = \strtok($this->string, $this->tokens);
    
        while ($item->value !== false) {
            $item->key = $this->index++;
            yield;
        
            $item->value = \strtok($this->tokens);
        }
    }
    
    public function restartWith(string $string, ?string $tokens = null): void
    {
        $this->index = 0;
        $this->string = $string;
    
        if ($tokens !== null) {
            $this->tokens = $tokens;
        }
    }
}