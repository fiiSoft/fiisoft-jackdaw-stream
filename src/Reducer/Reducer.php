<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Reducer;

use FiiSoft\Jackdaw\Consumer\ConsumerReady;
use FiiSoft\Jackdaw\Mapper\MapperReady;
use FiiSoft\Jackdaw\Operation\Internal\DispatchReady;
use FiiSoft\Jackdaw\Operation\Internal\ForkReady;
use FiiSoft\Jackdaw\Transformer\TransformerReady;

interface Reducer extends ConsumerReady, MapperReady, DispatchReady, ForkReady, TransformerReady
{
    /**
     * @param mixed $value
     */
    public function consume($value): void;
    
    public function hasResult(): bool;
    
    /**
     * @return mixed|null
     */
    public function result();
    
    public function reset(): void;
}