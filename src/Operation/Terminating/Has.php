<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Stream;

final class Has extends SimpleFinal
{
    private Filter $filter;
    
    private bool $has = false;
    
    /**
     * @param Filter|callable|mixed $predicate
     */
    public function __construct(Stream $stream, $predicate, int $mode = Check::VALUE)
    {
        $this->filter = Filters::getAdapter($predicate, $mode);
        
        parent::__construct($stream);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            $this->has = true;
            $signal->stop();
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($value, $key)) {
                $this->has = true;
                break;
            }
        }
        
        yield;
    }
    
    public function hasResult(): bool
    {
        return true;
    }
    
    public function getResult(): Item
    {
        return new Item(0, $this->has);
    }
}