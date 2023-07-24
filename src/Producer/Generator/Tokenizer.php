<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Producer\Tech\NonCountableProducer;

final class Tokenizer extends NonCountableProducer
{
    private string $tokens;
    private string $string;
    
    private bool $keepIndex = false;
    private int $index = 0;
    
    public function __construct(string $tokens, string $string = '')
    {
        $this->tokens = $tokens;
        $this->string = $string;
    }
    
    public function feed(Item $item): \Generator
    {
        if (!$this->keepIndex) {
            $this->index = 0;
        }
        
        $value = \strtok($this->string, $this->tokens);
        
        while ($value !== false) {
            $item->key = $this->index++;
            $item->value = $value;
            yield;
        
            $value = \strtok($this->tokens);
        }
    }
    
    public function restartWith(string $string, ?string $tokens = null): void
    {
        $this->string = $string;
    
        if ($tokens !== null) {
            $this->tokens = $tokens;
        }
    }
    
    public function keepIndex(): void
    {
        $this->keepIndex = true;
    }
}