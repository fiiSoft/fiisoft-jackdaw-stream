<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Tokenize extends BaseMapper
{
    private string $tokens;
    
    public function __construct(string $tokens)
    {
        $this->tokens = $tokens;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key)
    {
        if (\is_string($value)) {
            $result = [];
            
            $token = \strtok($value, $this->tokens);
            while ($token !== false) {
                $result[] = $token;
                $token = \strtok($this->tokens);
            }
            
            return $result;
        }
        
        throw new \LogicException('Value must be a string to tokenize it');
    }
    
    public function tokens(): string
    {
        return $this->tokens;
    }
}