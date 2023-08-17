<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Reducer\Internal\BaseReducer;

final class Max extends BaseReducer
{
    /** @var float|int|null */
    private $result;
    
    /**
     * @param float|int $value
     * @return void
     */
    public function consume($value): void
    {
        if ($this->result === null) {
            $this->result = $value;
        } elseif ($value > $this->result) {
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
    
    public function reset(): void
    {
        $this->result = null;
    }
}