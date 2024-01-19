<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Special;

use FiiSoft\Jackdaw\Internal\Iterator\Interruption;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Iterate extends BaseOperation
{
    private Interruption $interruption;
    
    public function __construct()
    {
        $this->interruption = new Interruption();
    }
    
    public function handle(Signal $signal): void
    {
        throw $this->interruption;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        return $stream;
    }
}