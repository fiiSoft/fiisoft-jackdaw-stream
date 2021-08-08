<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Predicate\Predicates;
use FiiSoft\Jackdaw\Stream;

final class Find extends FinalOperation implements ResultProvider
{
    private Predicate $predicate;
    private int $mode;
    
    private ?Item $item = null;
    
    /**
     * @param Stream $stream
     * @param Predicate|Filter|callable|mixed $predicate
     * @param int $mode
     */
    public function __construct(Stream $stream, $predicate, int $mode = Check::VALUE)
    {
        $this->predicate = Predicates::getAdapter($predicate);
        $this->mode = Check::getMode($mode);
        
        parent::__construct($stream, $this);
    }
    
    public function handle(Signal $signal): void
    {
        if ($this->predicate->isSatisfiedBy($signal->item->value, $signal->item->key, $this->mode)) {
            $this->item = $signal->item->copy();
            $signal->stop();
        }
    }
    
    public function hasResult(): bool
    {
        return $this->item !== null;
    }
    
    public function getResult(): Item
    {
        return $this->item;
    }
}