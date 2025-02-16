<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class MapArgs extends BaseOperation
{
    /** @var callable */
    private $consumer;
    
    public function __construct(callable $consumer)
    {
        $this->consumer = $consumer;
    }
    
    public function handle(Signal $signal): void
    {
        $signal->item->value = ($this->consumer)(...\array_values($signal->item->value));
        
        $this->next->handle($signal);
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => ($this->consumer)(...\array_values($value));
        }
    }
}