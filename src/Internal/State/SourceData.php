<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal\State;

use FiiSoft\Jackdaw\Internal\Pipe;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Stream;

final class SourceData
{
    public NextValue $nextValue;
    public Stream $stream;
    public Signal $signal;
    public Pipe $pipe;
    public Sources $sources;
    
    public function __construct(
        Stream $stream,
        Signal $signal,
        Pipe $pipe,
        Sources $sources
    ) {
        $this->stream = $stream;
        $this->signal = $signal;
        $this->pipe = $pipe;
        $this->sources = $sources;
        
        $this->nextValue = new NextValue();
    }
}