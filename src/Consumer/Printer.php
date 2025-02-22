<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer;

use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

final class Printer implements Consumer
{
    private int $mode;
    
    public function __construct(int $mode = Check::BOTH)
    {
        $this->mode = Mode::get($mode);
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
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $this->consume($value, $key);
            
            yield $key => $value;
        }
    }
}