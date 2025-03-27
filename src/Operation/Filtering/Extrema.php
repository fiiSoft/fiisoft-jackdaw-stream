<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Filtering;

use FiiSoft\Jackdaw\Comparator\ComparatorReady;
use FiiSoft\Jackdaw\Comparator\Comparison\Comparison;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparator;
use FiiSoft\Jackdaw\Comparator\ItemComparator\ItemComparatorFactory;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class Extrema extends BaseOperation
{
    private const FLAT = 0, UP = 1, DOWN = 2;
    
    private ItemComparator $comparator;
    private ?Item $previous = null;
    private Item $item;
    
    private int $direction;
    private bool $allowLimits, $isFirst = true;
    
    /** @var ComparatorReady|callable|null */
    private $comparison;
    
    /**
     * @param ComparatorReady|callable|null $comparison
     */
    public function __construct(bool $allowLimits = true, $comparison = null)
    {
        $this->allowLimits = $allowLimits;
        $this->comparison = $comparison;
    }
    
    public function prepare(): void
    {
        parent::prepare();
        
        $this->direction = $this->allowLimits ? self::UP : self::FLAT;
        $this->comparator = ItemComparatorFactory::getForComparison(Comparison::prepare($this->comparison));
        $this->item = new Item();
        
        $this->comparison = null;
    }
    
    public function handle(Signal $signal): void
    {
        $this->item = $signal->item;
        
        if ($this->previous === null) {
            $this->previous = clone $this->item;
        } else {
            $compare = $this->comparator->compare($this->previous, $this->item);
            if ($compare === 0) {
                $this->direction = self::FLAT;
                $this->isFirst = false;
            } elseif ($this->isFirst) {
                if ($this->allowLimits) {
                    $signal->item = $this->previous;
                    $this->next->handle($signal);
                    $signal->item = $this->item;
                }
                
                $this->previous->key = $this->item->key;
                $this->previous->value = $this->item->value;
                
                $this->direction = $compare < 0 ? self::UP : self::DOWN;
                $this->isFirst = false;
            } elseif ($compare < 0) {
                if ($this->direction === self::DOWN) {
                    $signal->item = $this->previous;
                    $this->next->handle($signal);
                    $signal->item = $this->item;
                }
                
                $this->previous->key = $this->item->key;
                $this->previous->value = $this->item->value;
                
                $this->direction = self::UP;
            } else {
                if ($this->direction === self::UP) {
                    $signal->item = $this->previous;
                    $this->next->handle($signal);
                    $signal->item = $this->item;
                }
                
                $this->previous->key = $this->item->key;
                $this->previous->value = $this->item->value;
                
                $this->direction = self::DOWN;
            }
        }
    }
    
    public function buildStream(iterable $stream): iterable
    {
        $item = $this->item;
        
        foreach ($stream as $item->key => $item->value) {
            if ($this->previous === null) {
                $this->previous = clone $item;
            } else {
                $compare = $this->comparator->compare($this->previous, $item);
                if ($compare === 0) {
                    $this->direction = self::FLAT;
                    $this->isFirst = false;
                } elseif ($this->isFirst) {
                    if ($this->allowLimits) {
                        yield $this->previous->key => $this->previous->value;
                    }
                    
                    $this->previous->key = $item->key;
                    $this->previous->value = $item->value;
                    
                    $this->direction = $compare < 0 ? self::UP : self::DOWN;
                    $this->isFirst = false;
                } elseif ($compare < 0) {
                    if ($this->direction === self::DOWN) {
                        yield $this->previous->key => $this->previous->value;
                    }
                    
                    $this->previous->key = $item->key;
                    $this->previous->value = $item->value;
                    
                    $this->direction = self::UP;
                } else {
                    if ($this->direction === self::UP) {
                        yield $this->previous->key => $this->previous->value;
                    }
                    
                    $this->previous->key = $item->key;
                    $this->previous->value = $item->value;
                    
                    $this->direction = self::DOWN;
                }
            }
        }
        
        if ($this->allowLimits && $this->direction !== self::FLAT && $this->previous !== null) {
            $this->direction = self::FLAT;
            yield $item->key => $item->value;
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty && $this->allowLimits && $this->direction !== self::FLAT && $this->previous !== null) {
            
            $signal->resume();
            $this->next->handle($signal);
            
            $this->direction = self::FLAT;
            
            return true;
        }
        
        return parent::streamingFinished($signal);
    }
}