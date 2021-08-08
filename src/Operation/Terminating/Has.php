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

final class Has extends FinalOperation implements ResultProvider
{
    /** @var Predicate */
    private $predicate;
    
    /** @var bool */
    private $has = false;
    
    /** @var int */
    private $mode;
    
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
    
    public function handle(Signal $signal)
    {
        $item = $signal->item;
        
        if ($this->predicate->isSatisfiedBy($item->value, $item->key, $this->mode)) {
            $this->has = true;
            $signal->stop();
        }
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