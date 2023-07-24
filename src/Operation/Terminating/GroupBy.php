<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamCollection;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Operation\Internal\DataCollector;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Producer\Producer;

final class GroupBy extends BaseOperation implements DataCollector
{
    private Discriminator $discriminator;
    private bool $reindex;
    
    private array $collections = [];
    
    /**
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|string|int $discriminator
     */
    public function __construct($discriminator, bool $reindex = false)
    {
        $this->discriminator = Discriminators::getAdapter($discriminator);
        $this->reindex = $reindex;
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        $classifier = $this->discriminator->classify($item->value, $item->key);
    
        if (\is_bool($classifier)) {
            $classifier = (int) $classifier;
        } elseif (!\is_string($classifier) && !\is_int($classifier)) {
            throw new \UnexpectedValueException(
                'Value returned from discriminator is inappropriate (got '.Helper::typeOfParam($classifier).')'
            );
        }
        
        if ($this->reindex) {
            $this->collections[$classifier][] = $item->value;
        } else {
            $this->collections[$classifier][$item->key] = $item->value;
        }
    }
 
    public function result(): StreamCollection
    {
        return new StreamCollection($this->collections);
    }
    
    public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($producer->feed($item) as $_) {
            $classifier = $this->discriminator->classify($item->value, $item->key);
            
            if (\is_bool($classifier)) {
                $classifier = (int) $classifier;
            } elseif (!\is_string($classifier) && !\is_int($classifier)) {
                throw new \UnexpectedValueException(
                    'Value returned from discriminator is inappropriate (got '.Helper::typeOfParam($classifier).')'
                );
            }
            
            if ($this->reindex) {
                $this->collections[$classifier][] = $item->value;
            } else {
                $this->collections[$classifier][$item->key] = $item->value;
            }
        }
        
        return $this->streamingFinished($signal);
    }

    public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
    {
        $item = $signal->item;
        
        foreach ($data as $item->key => $item->value) {
            $classifier = $this->discriminator->classify($item->value, $item->key);
            
            if (\is_bool($classifier)) {
                $classifier = (int) $classifier;
            } elseif (!\is_string($classifier) && !\is_int($classifier)) {
                throw new \UnexpectedValueException(
                    'Value returned from discriminator is inappropriate (got '.Helper::typeOfParam($classifier).')'
                );
            }
            
            if ($this->reindex) {
                $this->collections[$classifier][] = $item->value;
            } else {
                $this->collections[$classifier][$item->key] = $item->value;
            }
        }
        
        return $this->streamingFinished($signal);
    }
    
    /**
     * @param bool $reindexed
     * @param Item[] $items
     */
    public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
    {
        foreach ($items as $item) {
            $classifier = $this->discriminator->classify($item->value, $item->key);
            
            if (\is_bool($classifier)) {
                $classifier = (int) $classifier;
            } elseif (!\is_string($classifier) && !\is_int($classifier)) {
                throw new \UnexpectedValueException(
                    'Value returned from discriminator is inappropriate (got '.Helper::typeOfParam($classifier).')'
                );
            }
            
            if ($this->reindex) {
                $this->collections[$classifier][] = $item->value;
            } else {
                $this->collections[$classifier][$item->key] = $item->value;
            }
        }
        
        return $this->streamingFinished($signal);
    }
}