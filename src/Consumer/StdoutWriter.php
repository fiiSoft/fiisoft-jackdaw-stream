<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\Check;

final class StdoutWriter implements Consumer
{
    private int $mode;
    private string $newLine;
    
    public function __construct(bool $addNewLine = true, int $mode = Check::VALUE)
    {
        $this->newLine = $addNewLine ? \PHP_EOL : '';
        $this->mode = Check::getMode($mode);
    }
    
    public function consume($value, $key): void
    {
        switch ($this->mode) {
            case Check::VALUE:
                echo $value, $this->newLine;
            break;
            
            case Check::KEY:
                echo $key, $this->newLine;
            break;
            
            default:
                echo $key, ':', $value, $this->newLine;
        }
    }
}