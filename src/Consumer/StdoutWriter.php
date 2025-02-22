<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

final class StdoutWriter implements Consumer
{
    private int $mode;
    private string $separator;
    
    public function __construct(string $separator = \PHP_EOL, int $mode = Check::VALUE)
    {
        $this->separator = $separator;
        $this->mode = Mode::get($mode);
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
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        switch ($this->mode) {
            case Check::VALUE:
                foreach ($stream as $key => $value) {
                    echo $value, $this->separator;
                    yield $key => $value;
                }
            break;
            
            case Check::KEY:
                foreach ($stream as $key => $value) {
                    echo $key, $this->separator;
                    yield $key => $value;
                }
            break;
            
            default:
                foreach ($stream as $key => $value) {
                    echo $key, ':', $value, $this->separator;
                    yield $key => $value;
                }
        }
    }
}