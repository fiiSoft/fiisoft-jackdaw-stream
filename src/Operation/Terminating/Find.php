<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Stream;

final class Find extends SimpleFinal
{
    private Filter $filter;
    
    private ?Item $item = null;
    
    /**
     * @param FilterReady|callable|mixed $predicate
     */
    public function __construct(Stream $stream, $predicate, int $mode = Check::VALUE)
    {
        $this->filter = Filters::getAdapter($predicate, $mode);
        
        parent::__construct($stream);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->filter->isAllowed($signal->item->value, $signal->item->key)) {
            $this->item = $signal->item->copy();
            $signal->stop();
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if ($this->filter->isAllowed($value, $key)) {
                $this->item = new Item($key, $value);
                break;
            }
        }
        
        yield;
    }
    
    public function hasResult(): bool
    {
        return $this->item !== null;
    }
    
    public function getResult(): Item
    {
        return $this->item;
    }
    
    protected function __clone()
    {
        parent::__clone();
    
        $this->item = null;
    }
}