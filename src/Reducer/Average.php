<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Internal\Item;

final class Average implements Reducer
{
    /** @var int */
    private $count = 0;
    
    /** @var float|int */
    private $total = 0;
    
    /**
     * @param float|int $value
     * @param string|int|null $key
     * @return void
     */
    public function consume($value, $key = null)
    {
        $this->total += $value;
        
        ++$this->count;
    }
    
    /**
     * @return float|int
     */
    public function result()
    {
        return $this->total / $this->count;
    }
    
    public function hasResult(): bool
    {
        return $this->count !== 0;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->result());
    }
}