<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Consumer\Adapter;

use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Registry\RegWriter;

final class RegWriterAdapter implements Consumer
{
    private RegWriter $regWriter;
    
    public function __construct(RegWriter $regWriter)
    {
        $this->regWriter = $regWriter;
    }
    
    /**
     * @inheritDoc
     */
    public function consume($value, $key): void
    {
        $this->regWriter->write($value, $key);
    }
}