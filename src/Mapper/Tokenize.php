<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Tokenize extends StateMapper
{
    private string $tokens;
    
    public function __construct(string $tokens)
    {
        $this->tokens = $tokens;
    }
    
    /**
     * @param string $value
     * @param mixed $key
     * @return string[]
     */
    public function map($value, $key = null): array
    {
        $result = [];
        
        $token = \strtok($value, $this->tokens);
        while ($token !== false) {
            $result[] = $token;
            $token = \strtok($this->tokens);
        }
        
        return $result;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $result = [];
            
            $token = \strtok($value, $this->tokens);
            while ($token !== false) {
                $result[] = $token;
                $token = \strtok($this->tokens);
            }
            
            yield $key => $result;
        }
    }
    
    public function tokens(): string
    {
        return $this->tokens;
    }
}