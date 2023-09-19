<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Discriminator\ByKey;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Producer\Producer;

abstract class GroupingOperation extends BaseOperation implements DataCollector
{
    protected array $collections = [];
    protected bool $reindex;
    
    private Discriminator $discriminator;
    
    /**
     * @param DiscriminatorReady|callable|array|string|int $discriminator
     */
    public function __construct($discriminator, ?bool $reindex = null)
    {
        if ($discriminator instanceof ByKey) {
            if ($reindex === null) {
                $reindex = true;
            }
        } elseif ($reindex === null) {
            $reindex = false;
        }
        
        $this->discriminator = Discriminators::prepare($discriminator);
        $this->reindex = $reindex;
    }
    
    final public function handle(Signal $signal): void
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
    
    final public function collectDataFromProducer(Producer $producer, Signal $signal, bool $reindexed): bool
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
    
    final public function acceptSimpleData(array $data, Signal $signal, bool $reindexed): bool
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
     * @param Item[] $items
     */
    final public function acceptCollectedItems(array $items, Signal $signal, bool $reindexed): bool
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
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->collections = [];
            
            parent::destroy();
        }
    }
}