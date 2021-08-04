<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Internal\Item;

final class Concat implements Reducer
{
    /** @var string */
    private $separator;
    
    /** @var string */
    private $result = '';
    
    /** @var bool */
    private $hasAny = false;
    
    public function __construct(string $separator = '')
    {
        $this->separator = $separator;
    }
    
    public function consume($value, $key = null)
    {
        $this->hasAny = true;
        
        if ($this->result === '') {
            $this->result = (string) $value;
        } else {
            $this->result .= $this->separator.$value;
        }
    }
    
    public function result()
    {
        return $this->result;
    }
    
    public function hasResult(): bool
    {
        return $this->hasAny;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->result());
    }
}