<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;

final class ChunkBy extends BaseOperation
{
    private Discriminator $discriminator;
    private bool $reindex;
    
    /** @var string|int|null */
    private $previous = null;
    
    private array $chunked = [];
    
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
                'Unsupported value was returned from discriminator (got '.Helper::typeOfParam($classifier).')'
            );
        }
    
        if ($this->previous === $classifier) {
            if ($this->reindex) {
                $this->chunked[] = $item->value;
            } else {
                $this->chunked[$item->key] = $item->value;
            }
        } elseif ($this->previous === null) {
            $this->previous = $classifier;
            
            if ($this->reindex) {
                $this->chunked[] = $item->value;
            } else {
                $this->chunked[$item->key] = $item->value;
            }
        } else {
            $chunked = $this->chunked;
            $this->chunked = [];
    
            if ($this->reindex) {
                $this->chunked[] = $item->value;
            } else {
                $this->chunked[$item->key] = $item->value;
            }
            
            $item->value = $chunked;
            $item->key = $this->previous;
            $this->previous = $classifier;
    
            $this->next->handle($signal);
        }
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        if ($signal->isEmpty && !empty($this->chunked)) {
            $signal->resume();
    
            $signal->item->value = $this->chunked;
            $signal->item->key = $this->previous;
            
            $this->chunked = [];
            $this->previous = null;
    
            $this->next->handle($signal);
            
            return true;
        }
        
        return parent::streamingFinished($signal);
    }
}