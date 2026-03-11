<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

interface Counter extends Consumer
{
    /**
     * It returns current (running) value of counter.
     */
    public function get(): int;
}