<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Collecting;

use FiiSoft\Jackdaw\Comparator\Comparable;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\Limitable;
use FiiSoft\Jackdaw\Operation\Collecting\SortLimited\MultiSortLimited;
use FiiSoft\Jackdaw\Operation\Collecting\SortLimited\SingleSortLimited;
use FiiSoft\Jackdaw\Producer\Producer;

abstract class SortLimited extends BaseOperation implements Limitable
{
    protected Sorting $sorting;
    
    /**
     * @param Comparable|callable|null $sorting
     */
    final public static function create(int $limit, $sorting = null): self
    {
        $sorting = Sorting::prepare($sorting);
        
        return $limit === 1
            ? new SingleSortLimited($sorting)
            : new MultiSortLimited($limit, $sorting);
    }
    
    protected function __construct(Sorting $sorting)
    {
        $this->sorting = $sorting;
    }
    
    final public function createWithLimit(int $limit): Limitable
    {
        return self::create($limit, $this->sorting);
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($this->isEmpty()) {
            return parent::streamingFinished($signal);
        }
        
        $signal->restartWith($this->createProducer(), $this->next);
        
        return true;
    }
    
    abstract protected function createProducer(): Producer;
    
    abstract protected function isEmpty(): bool;
}