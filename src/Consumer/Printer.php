<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\Check;

final class Printer implements Consumer
{
    private int $mode;
    
    public function __construct(int $mode = Check::BOTH)
    {
        $this->mode = Check::getMode($mode);
    }
    
    public function consume($value, $key): void
    {
        switch ($this->mode) {
            case Check::BOTH:
            case Check::ANY:
                echo 'key: ', $key, ', value: ', $value, \PHP_EOL;
            break;
            case Check::VALUE:
                echo 'value: ', $value, \PHP_EOL;
            break;
            case Check::KEY:
                echo 'key: ', $key, \PHP_EOL;
            break;
        }
    }
}