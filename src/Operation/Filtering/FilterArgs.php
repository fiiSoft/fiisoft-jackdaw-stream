<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class FilterArgs extends BaseOperation
{
    /** @var callable */
    private $consumer;
    
    public function __construct(callable $consumer)
    {
        $this->consumer = $consumer;
    }
    
    public function handle(Signal $signal): void
    {
        if (($this->consumer)(...\array_values($signal->item->value))) {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (($this->consumer)(...\array_values($value))) {
                yield $key => $value;
            }
        }
    }
}