<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

final class Shortest implements Reducer
{
    private ?string $result = null;
    private int $length = 0;
    
    /**
     * @param string $value
     * @return void
     */
    public function consume($value): void
    {
        if ($this->result === null) {
            $this->result = (string) $value;
            $this->length = \mb_strlen($this->result);
        } else {
            $value = (string) $value;
            $length = \mb_strlen($value);
            
            if ($length < $this->length) {
                $this->result = $value;
                $this->length = $length;
            }
        }
    }
    
    public function result(): string
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
        $this->length = 0;
    }
}