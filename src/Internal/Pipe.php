<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Internal\Exception\PipeExceptionFactory;
use FiiSoft\Jackdaw\Mapper\Key;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Mapper\Tokenize as TokenizeMapper;
use FiiSoft\Jackdaw\Mapper\Value;
use FiiSoft\Jackdaw\Operation\Collecting\Categorize;
use FiiSoft\Jackdaw\Operation\Collecting\Gather;
use FiiSoft\Jackdaw\Operation\Collecting\Reverse;
use FiiSoft\Jackdaw\Operation\Collecting\Segregate;
use FiiSoft\Jackdaw\Operation\Collecting\ShuffleAll;
use FiiSoft\Jackdaw\Operation\Collecting\Sort;
use FiiSoft\Jackdaw\Operation\Collecting\SortLimited;
use FiiSoft\Jackdaw\Operation\Collecting\Tail;
use FiiSoft\Jackdaw\Operation\Filtering\EveryNth;
use FiiSoft\Jackdaw\Operation\Filtering\Filter;
use FiiSoft\Jackdaw\Operation\Filtering\FilterBy;
use FiiSoft\Jackdaw\Operation\Filtering\FilterByMany;
use FiiSoft\Jackdaw\Operation\Filtering\FilterMany;
use FiiSoft\Jackdaw\Operation\Filtering\FilterSingle;
use FiiSoft\Jackdaw\Operation\Mapping\Flip;
use FiiSoft\Jackdaw\Operation\Filtering\OmitReps;
use FiiSoft\Jackdaw\Operation\Filtering\Skip;
use FiiSoft\Jackdaw\Operation\Filtering\SkipWhile;
use FiiSoft\Jackdaw\Operation\Filtering\Unique;
use FiiSoft\Jackdaw\Operation\Internal\Limitable;
use FiiSoft\Jackdaw\Operation\Internal\Pipe\Initial;
use FiiSoft\Jackdaw\Operation\Internal\Reindexable;
use FiiSoft\Jackdaw\Operation\Internal\Shuffle;
use FiiSoft\Jackdaw\Operation\LastOperation;
use FiiSoft\Jackdaw\Operation\Mapping\Accumulate;
use FiiSoft\Jackdaw\Operation\Mapping\Aggregate;
use FiiSoft\Jackdaw\Operation\Mapping\Chunk;
use FiiSoft\Jackdaw\Operation\Mapping\ChunkBy;
use FiiSoft\Jackdaw\Operation\Mapping\Classify;
use FiiSoft\Jackdaw\Operation\Mapping\ConditionalMap;
use FiiSoft\Jackdaw\Operation\Mapping\Flat;
use FiiSoft\Jackdaw\Operation\Mapping\Map;
use FiiSoft\Jackdaw\Operation\Mapping\MapFieldWhen;
use FiiSoft\Jackdaw\Operation\Mapping\MapKey;
use FiiSoft\Jackdaw\Operation\Mapping\MapKeyValue;
use FiiSoft\Jackdaw\Operation\Mapping\MapMany;
use FiiSoft\Jackdaw\Operation\Mapping\MapWhen;
use FiiSoft\Jackdaw\Operation\Mapping\Reindex;
use FiiSoft\Jackdaw\Operation\Mapping\Scan;
use FiiSoft\Jackdaw\Operation\Mapping\Tokenize;
use FiiSoft\Jackdaw\Operation\Mapping\Tuple;
use FiiSoft\Jackdaw\Operation\Mapping\UnpackTuple;
use FiiSoft\Jackdaw\Operation\Mapping\Window;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Sending\Feed;
use FiiSoft\Jackdaw\Operation\Sending\FeedMany;
use FiiSoft\Jackdaw\Operation\Sending\SendTo;
use FiiSoft\Jackdaw\Operation\Sending\SendToMany;
use FiiSoft\Jackdaw\Operation\Special\Limit;
use FiiSoft\Jackdaw\Operation\Special\Until;
use FiiSoft\Jackdaw\Operation\Terminating\Collect;
use FiiSoft\Jackdaw\Operation\Terminating\CollectKeys;
use FiiSoft\Jackdaw\Operation\Terminating\Count;
use FiiSoft\Jackdaw\Operation\Terminating\FinalOperation;
use FiiSoft\Jackdaw\Operation\Terminating\Find;
use FiiSoft\Jackdaw\Operation\Terminating\First;
use FiiSoft\Jackdaw\Operation\Terminating\Has;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly;
use FiiSoft\Jackdaw\Operation\Terminating\IsEmpty;
use FiiSoft\Jackdaw\Operation\Terminating\Last;
use FiiSoft\Jackdaw\Stream;

final class Pipe extends ProtectedCloning implements Destroyable
{
    public Operation $head;
    public Operation $last;
    
    /** @var Operation[] */
    public array $stack = [];
    
    /** @var Operation|LastOperation|null  */
    private $replacement = null;
    
    private bool $isPrepared = false;
    private bool $isDestroying = false;
    private bool $isResuming = false;
    
    public function __construct()
    {
        $this->head = new Initial();
        $this->last = $this->head;
    }
    
    public function __clone()
    {
        if (!empty($this->stack)) {
            throw PipeExceptionFactory::cannotClonePipeWithNoneEmptyStack();
        }
        
        $this->head = clone $this->head;
        $this->last = $this->head->getLast();
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->head->destroy();
            $this->last->destroy();
            
            foreach ($this->stack as $operation) {
                $operation->destroy();
            }
            
            $this->stack = [];
        }
    }
    
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    public function buildStream(iterable $stream): iterable
    {
        for ($node = $this->head; $node !== null; $node = $node->getNext()) {
            $stream = $node->buildStream($stream);
        }
        
        return $stream;
    }
    
    public function prepare(): void
    {
        if (!$this->isPrepared) {
            $this->head = $this->head->removeFromChain();
            $this->isPrepared = true;
        }
    }
    
    /**
     * @return Operation|LastOperation
     */
    public function chainOperation(Operation $operation, Stream $stream)
    {
        if ($this->cannotChain($operation)) {
            throw PipeExceptionFactory::cannotAddOperationToStartedStream();
        }
        
        if ($this->canAppend($operation, $stream)) {
            $this->append($operation);
        }
        
        if ($this->replacement === null) {
            return $operation;
        }
        
        $replacement = $this->replacement;
        $this->replacement = null;
        
        return $replacement;
    }
    
    private function cannotChain(Operation $operation): bool
    {
        return $this->isPrepared && !$this->isFeedOperation($operation);
    }
    
    private function isFeedOperation(Operation $operation): bool
    {
        return $operation instanceof Feed || $operation instanceof FeedMany;
    }
    
    public function append(Operation $operation): void
    {
        $this->last = $this->last->setNext($operation);
    }
    
    private function canAppend(Operation $next, Stream $stream): bool
    {
        if ($this->last instanceof FinalOperation) {
            throw PipeExceptionFactory::cannotAddOperationToFinalOne();
        }
        
        if ($next instanceof FilterSingle) {
            if ($this->last instanceof FilterMany) {
                $this->last->add($next);
                return false;
            }
            if ($this->last instanceof FilterSingle) {
                $this->replaceLastOperation(new FilterMany($this->last, $next));
                return false;
            }
            if ($this->keepsItemsUnchanged($this->last)) {
                $node = $this->findPlaceForFilterMany($this->last);
                if ($node instanceof FilterMany) {
                    $node->add($next);
                } else {
                    $node->getNext()->prepend(new FilterMany($next));
                }
                return false;
            }
        } elseif ($next instanceof Map) {
            if ($next->mapper() instanceof Value) {
                return false;
            }
            if ($this->last instanceof MapKey) {
                return !($this->last->mapper() instanceof Value && $next->mapper() instanceof Key);
            }
            if ($this->last instanceof Flip && $next->mapper() instanceof Key) {
                $this->removeLast();
                $stream->mapKey(Mappers::value());
                return false;
            }
            if ($this->last instanceof MapMany) {
                $this->last->add($next);
                return false;
            }
            if ($this->last instanceof Map) {
                if (!$this->last->mergeWith($next)) {
                    $this->replaceLastOperation(new MapMany($this->last, $next));
                }
                return false;
            }
        } elseif ($next instanceof FilterBy) {
            if ($this->last instanceof FilterByMany) {
                $this->last->add($next);
                return false;
            }
            if ($this->last instanceof FilterBy) {
                $this->replaceLastOperation(new FilterByMany($this->last, $next));
                return false;
            }
            if ($this->keepsItemsUnchanged($this->last)) {
                $node = $this->findPlaceForFilterByMany($this->last);
                if ($node instanceof FilterByMany) {
                    $node->add($next);
                } else {
                    $node->getNext()->prepend(new FilterByMany($next));
                }
                return false;
            }
        } elseif ($next instanceof MapKey) {
            if ($next->mapper() instanceof Key) {
                return false;
            }
            if ($this->last instanceof Flip && $next->mapper() instanceof Value) {
                $this->removeLast();
                $stream->map(Mappers::key());
                return false;
            }
            if ($this->last instanceof Map) {
                return !($this->last->mapper() instanceof Key && $next->mapper() instanceof Value);
            }
            if ($this->last instanceof MapKey) {
                return !$this->last->mergeWith($next);
            }
        } elseif ($next instanceof Limit) {
            if ($this->last instanceof Limitable) {
                if ($this->last->applyLimit($next->limit())) {
                    return false;
                }
                $this->replaceLastOperation($this->last->createWithLimit($next->limit()));
                return true;
            }
            if ($this->last instanceof Sort) {
                $this->replaceLastOperation($this->last->createSortLimited($next->limit()));
                return false;
            }
            if ($this->last instanceof Reverse) {
                $this->removeLast();
                $stream->tail($next->limit())->reverse();
                return false;
            }
        } elseif ($next instanceof Skip) {
            if ($this->last instanceof Skip) {
                $this->last->mergeWith($next);
                return false;
            }
        } elseif ($next instanceof Reverse) {
            if ($this->last instanceof Reverse) {
                $this->removeLast();
                return false;
            }
            if ($this->last instanceof Shuffle) {
                return false;
            }
            if ($this->last instanceof Limitable && $this->last->limit() === 1) {
                return false;
            }
            if ($this->last instanceof Sort) {
                $this->last->reverseOrder();
                return false;
            }
        } elseif ($next instanceof Reindex) {
            if ($this->last instanceof Reindex) {
                $this->last->mergeWith($next);
                return false;
            }
            if ($this->last instanceof MapKey) {
                $this->removeLast();
            } elseif ($next->isDefaultReindex()) {
                if ($this->last instanceof Gather
                    || $this->last instanceof Accumulate
                    || $this->last instanceof Aggregate
                    || $this->last instanceof Chunk
                    || $this->last instanceof Segregate
                    || $this->last instanceof Tokenize
                    || $this->last instanceof Tuple
                ) {
                    return false;
                }
            }
        } elseif ($next instanceof Flip) {
            if ($this->last instanceof Flip) {
                $this->removeLast();
                return false;
            }
            if ($this->last instanceof Map && $this->last->mapper() instanceof Key) {
                $this->removeLast();
                $stream->map(Mappers::key());
                return false;
            }
            if ($this->last instanceof MapKey && $this->last->mapper() instanceof Value) {
                $this->removeLast();
                $stream->mapKey(Mappers::value());
                return false;
            }
        } elseif ($next instanceof Shuffle) {
            if ($this->last instanceof Shuffle) {
                $this->replaceLastOperation($this->last->mergedWith($next));
                return false;
            }
            if ($next instanceof ShuffleAll) {
                if ($this->last instanceof Reverse || $this->last instanceof Sort) {
                    $this->removeLast();
                }
            }
            if ($this->last instanceof Limitable && $this->last->limit() === 1) {
                return false;
            }
        } elseif ($next instanceof Tail) {
            if ($this->last instanceof Tail) {
                $this->last->mergeWith($next);
                return false;
            }
            if ($this->last instanceof Sort) {
                $this->last->reverseOrder();
                $stream->limit($next->length())->reverse();
                return false;
            }
            if ($this->last instanceof Limitable) {
                if ($this->last->limit() > $next->length()) {
                    $stream->skip($this->last->limit() - $next->length());
                }
                return false;
            }
            if ($this->last instanceof Reverse) {
                $this->removeLast();
                $stream->limit($next->length())->reverse();
                return false;
            }
        } elseif ($next instanceof Flat) {
            if ($this->last instanceof Flat) {
                $this->last->mergeWith($next);
                return false;
            }
            if ($this->last instanceof Map) {
                $mapper = $this->last->mapper();
                if ($mapper instanceof TokenizeMapper) {
                    $this->removeLast();
                    $stream->tokenize($mapper->tokens());
                    return false;
                }
            }
            if ($this->last instanceof Gather) {
                $reindex = $this->last->isReindexed();
                $this->removeLast();
                if ($reindex) {
                    $stream->reindex();
                }
                if ($next->isLevel(1)) {
                    return false;
                }
                if (!$next->isLevel(0)) {
                    $next->decreaseLevel();
                }
            }
            if ($this->last instanceof Chunk && !$this->last->isReindexed()) {
                $this->removeLast();
                return !$next->isLevel(1);
            }
        } elseif ($next instanceof SendTo) {
            if ($this->last instanceof SendTo) {
                $this->replaceLastOperation($this->last->createSendToMany($next));
                return false;
            }
            if ($this->last instanceof SendToMany) {
                $this->last->addConsumers($next->consumer());
                return false;
            }
        } elseif ($next instanceof SendToMany) {
            if ($this->last instanceof SendToMany) {
                $this->last->addConsumers(...$next->getConsumers());
                return false;
            }
            if ($this->last instanceof SendTo) {
                $next->addConsumers($this->last->consumer());
                $this->removeLast();
            }
        } elseif ($next instanceof Sort || $next instanceof SortLimited) {
            if ($this->changesTheOrder($this->last)) {
                $this->removeLast();
            }
        } elseif ($next instanceof Gather) {
            if ($this->last instanceof Reindex) {
                if ($next->isReindexed()) {
                    $this->removeLast();
                } elseif ($this->last->isDefaultReindex()) {
                    $this->removeLast();
                    $this->append($next->reindexed());
                    return false;
                }
            }
        } elseif ($next instanceof Feed) {
            if ($this->last instanceof FeedMany) {
                $this->last->add($next);
                return false;
            }
            if ($this->last instanceof Feed) {
                $this->replaceLastOperation($this->last->createFeedMany($next));
                return false;
            }
        } elseif ($next instanceof ConditionalMap) {
            if ($next->shouldBeNonConditional()) {
                $stream->map($next->getMaper());
                return false;
            }
            return !$next->isBarren();
        } elseif ($next instanceof First) {
            if ($this->last instanceof Sort) {
                $stream->limit(1);
            } elseif ($this->last instanceof Limit) {
                $this->removeLast();
            } elseif ($this->last instanceof Limitable) {
                if (!$this->last->applyLimit(1)) {
                    $this->replaceLastOperation($this->last->createWithLimit(1));
                }
            } elseif ($this->last instanceof Reverse) {
                $this->removeLast();
                $this->replacement = $stream->last();
                return false;
            } elseif ($this->last instanceof Filter) {
                $this->replaceTerminatingOperation($this->last->createFind($stream));
                return false;
            }
        } elseif ($next instanceof Last) {
            if ($this->last instanceof Sort) {
                $this->last->reverseOrder();
                $this->replacement = $stream->first();
                return false;
            }
            if ($this->last instanceof Reverse) {
                $this->removeLast();
                $this->replacement = $stream->first();
                return false;
            }
            if ($this->last instanceof Tail) {
                $this->removeLast();
            }
        } elseif ($next instanceof IsEmpty) {
            if ($this->last instanceof Sort
                || $this->last instanceof SortLimited
                || $this->last instanceof Unique
                || $this->last instanceof Tuple
                || $this->last instanceof Tail
                || $this->last instanceof Shuffle
                || $this->last instanceof Scan
                || $this->last instanceof Reverse
                || $this->last instanceof Reindex
                || $this->last instanceof Map
                || $this->last instanceof MapFieldWhen
                || $this->last instanceof MapKey
                || $this->last instanceof MapKeyValue
                || $this->last instanceof MapMany
                || $this->last instanceof MapWhen
                || $this->last instanceof Gather
                || $this->last instanceof Flip
                || $this->last instanceof Flat
                || $this->last instanceof ChunkBy
                || $this->last instanceof Chunk
                || $this->last instanceof Classify
                || $this->last instanceof Categorize
                || $this->last instanceof Segregate
                || $this->last instanceof OmitReps
            ) {
                $this->removeLast();
                return $this->canAppend($next, $stream);
            }
        } elseif ($next instanceof Find
            || $next instanceof Has
            || $next instanceof HasOnly
            || $next instanceof HasEvery
        ) {
            if ($this->changesTheOrder($this->last)) {
                $this->removeLast();
                return $this->canAppend($next, $stream);
            }
        } elseif ($next instanceof Count) {
            if ($this->keepsQuantity($this->last)) {
                $this->removeLast();
                return $this->canAppend($next, $stream);
            }
        } elseif ($next instanceof Unique) {
            if (!$this->isOmitRepsInPipe()) {
                $stream->omitReps($next->comparison());
            }
        } elseif ($next instanceof Collect) {
            if ($this->last instanceof Flip) {
                $this->replaceTerminatingOperation(new CollectKeys($stream));
                return false;
            }
        } elseif ($next instanceof CollectKeys) {
            if ($this->last instanceof Flip) {
                $this->replaceTerminatingOperation(Collect::create($stream, true));
                return false;
            }
        } elseif ($next instanceof UnpackTuple) {
            if ($this->last instanceof Tuple && $next->isAssoc() === $this->last->isAssoc()) {
                $this->removeLast();
                return false;
            }
            if ($this->last instanceof Reindex || $this->last instanceof MapKey) {
                $this->removeLast();
            }
        } elseif ($next instanceof Tuple) {
            if ($this->last instanceof UnpackTuple && $next->isAssoc() === $this->last->isAssoc()) {
                $this->removeLast();
                return false;
            }
        } elseif ($next instanceof Until && $next->shouldBeInversed()) {
            $this->append($next->createInversed());
            return false;
        } elseif ($next instanceof SkipWhile && $next->shouldBeInversed()) {
            $this->append($next->createInversed());
            return false;
        } elseif ($next instanceof Window && $next->isLikeChunk()) {
            $stream->chunk($next->size(), $next->reindex());
            return false;
        } elseif ($next instanceof EveryNth) {
            if ($next->num() === 1) {
                return false;
            }
            if ($this->last instanceof EveryNth) {
                $this->last->applyNum($next->num());
                return false;
            }
        }
        
        if ($next instanceof Reindexable) {
            if ($this->last instanceof Reindex && $next->isReindexed()) {
                $this->removeLast();
            }
        }

        return true;
    }
    
    private function replaceTerminatingOperation(Operation $operation): void
    {
        $this->replaceLastOperation($operation);
        $this->replacement = $operation;
    }
    
    private function replaceLastOperation(Operation $operation): void
    {
        $this->removeLast();
        $this->append($operation);
    }
    
    private function removeLast(): void
    {
        $this->last = $this->last->removeFromChain();
    }
    
    private function findPlaceForFilterMany(Operation $last): Operation
    {
        $prev = $last->getPrev();
        
        while ($this->keepsItemsUnchanged($prev) || $this->isFilterBy($prev)) {
            $prev = $prev->getPrev();
        }
        
        return $prev;
    }
    
    private function isFilterBy(Operation $operation): bool
    {
        return $operation instanceof FilterBy || $operation instanceof FilterByMany;
    }
    
    private function findPlaceForFilterByMany(Operation $last): Operation
    {
        $prev = $last->getPrev();
        
        while ($this->keepsItemsUnchanged($prev) || $this->isFilterRegular($prev)) {
            $prev = $prev->getPrev();
        }
        
        return $prev;
    }
    
    private function isFilterRegular(Operation $operation): bool
    {
        return $operation instanceof FilterSingle || $operation instanceof FilterMany;
    }
    
    private function keepsQuantity(Operation $operation): bool
    {
        return $operation instanceof Reindex || $this->changesTheOrder($operation);
    }
    
    private function keepsItemsUnchanged(Operation $operation): bool
    {
        return $operation instanceof Unique
            || $operation instanceof OmitReps
            || $this->changesTheOrder($operation);
    }
    
    private function changesTheOrder(Operation $operation): bool
    {
        return $operation instanceof Reverse
            || $operation instanceof Shuffle
            || $operation instanceof Sort;
    }
    
    private function isOmitRepsInPipe(): bool
    {
        $prev = $this->last;
        while ($prev !== null && ! $prev instanceof OmitReps) {
            $prev = $prev->getPrev();
        }
        
        return $prev !== null;
    }
    
    public function resume(): void
    {
        if (!$this->isResuming) {
            $this->isResuming = true;
            try {
                $this->head->resume();
            } finally {
                $this->isResuming = false;
            }
        }
    }
}