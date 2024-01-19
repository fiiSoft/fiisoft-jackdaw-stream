<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class Tokenizer extends BaseProducer
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
    
    public function getIterator(): \Generator
    {
        if (!$this->keepIndex) {
            $this->index = 0;
        }
        
        $value = \strtok($this->string, $this->tokens);
        
        while ($value !== false) {
            yield $this->index++ => $value;
        
            $value = \strtok($this->tokens);
        }
    }
    
    /**
     * @return $this fluent interface
     */
    public function restartWith(string $string, ?string $tokens = null): self
    {
        $this->string = $string;
    
        if ($tokens !== null) {
            $this->tokens = $tokens;
        }
        
        return $this;
    }
    
    public function keepIndex(): void
    {
        $this->keepIndex = true;
    }
    
    public function destroy(): void
    {
        $this->string = '';
    }
}