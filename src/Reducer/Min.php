<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Internal\Item;

final class Min implements Reducer
{
    /** @var float|int|null */
    private $result;
    
    /**
     * @param float|int $value
     * @return void
     */
    public function consume($value)
    {
        if ($this->result === null) {
            $this->result = $value;
        } elseif ($value < $this->result) {
            $this->result = $value;
        }
    }
    
    /**
     * @return float|int|null
     */
    public function result()
    {
        return $this->result;
    }
    
    public function hasResult(): bool
    {
        return $this->result !== null;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->result());
    }
}