<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class Longest implements Reducer
{
    private ?string $result = null;
    
    private int $length = -1;
    
    /**
     * @param string $value
     */
    public function consume($value): void
    {
        $value = (string) $value;
        $length = \mb_strlen($value);
        
        if ($length > $this->length) {
            $this->result = $value;
            $this->length = $length;
        }
    }
    
    public function result(): ?string
    {
        return $this->result;
    }
    
    public function hasResult(): bool
    {
        return $this->result !== null;
    }
    
    public function reset(): void
    {
        $this->result = null;
        $this->length = -1;
    }
}