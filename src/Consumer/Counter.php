<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

interface Counter extends Consumer
{
    /**
     * Experimental method, so better use get() instead.
     * It triggers iterating over all streams for which this counter is assigned (if any) and returns total count.
     */
    public function count(): int;
    
    /**
     * It returns current (running) value of counter and doesn't trigger iterating over assigned streams.
     */
    public function get(): int;
}