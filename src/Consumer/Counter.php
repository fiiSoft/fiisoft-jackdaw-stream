<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

final class Counter implements Consumer
{
    /** @var int */
    private $count = 0;
    
    public function consume($value, $key)
    {
        ++$this->count;
    }
    
    public function count(): int
    {
        return $this->count;
    }
}