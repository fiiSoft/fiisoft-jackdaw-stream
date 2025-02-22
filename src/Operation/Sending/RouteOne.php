<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Sending;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\DispatchOperation;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\Handler;
use FiiSoft\Jackdaw\Operation\Sending\Dispatcher\HandlerReady;

final class RouteOne extends DispatchOperation
{
    private Filter $condition;
    private Handler $handler;
    
    /**
     * @param FilterReady|callable|mixed $condition
     */
    public function __construct($condition, HandlerReady $handler)
    {
        parent::__construct([$handler]);
        
        $this->handler = $this->handlers[0];
        $this->condition = Filters::getAdapter($condition);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->condition->isAllowed($signal->item->value, $signal->item->key)) {
            $this->handler->handle($signal);
        } else {
            $this->next->handle($signal);
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->condition->isAllowed($value, $key)) {
                $this->handler->handlePair($value, $key);
            } else {
                yield $key => $value;
            }
        }
    }
}