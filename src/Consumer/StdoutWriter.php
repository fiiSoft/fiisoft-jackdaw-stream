<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\Check;

final class StdoutWriter implements Consumer
{
    private int $mode;
    private string $separator;
    
    public function __construct(string $separator = \PHP_EOL, int $mode = Check::VALUE)
    {
        $this->separator = $separator;
        $this->mode = Check::getMode($mode);
    }
    
    public function consume($value, $key): void
    {
        switch ($this->mode) {
            case Check::VALUE:
                echo $value, $this->separator;
            break;
            
            case Check::KEY:
                echo $key, $this->separator;
            break;
            
            default:
                echo $key, ':', $value, $this->separator;
        }
    }
}