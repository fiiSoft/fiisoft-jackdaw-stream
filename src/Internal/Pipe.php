<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Internal\Exception\PipeExceptionFactory;
use FiiSoft\Jackdaw\Internal\Pipe\CanAppendResult;
use FiiSoft\Jackdaw\Internal\Pipe\ChainOperationResult;
use FiiSoft\Jackdaw\Mapper\Key;
use FiiSoft\Jackdaw\Mapper\Mappers;
use FiiSoft\Jackdaw\Mapper\Tokenize as TokenizeMapper;
use FiiSoft\Jackdaw\Mapper\Value;
use FiiSoft\Jackdaw\Operation\Collecting\{Cache, Categorize, Fork, ForkMatch, Gather, Reverse, Segregate, ShuffleAll,
    Sort, SortLimited, Tail};
use FiiSoft\Jackdaw\Operation\Filtering\{EveryNth, FilterByMany, FilterMany, FilterOp, Omit, OmitReps, Skip, SkipNth,
    StackableFilter, StackableFilterBy, Unique};
use FiiSoft\Jackdaw\Operation\Internal\{Detachable, Limitable, Operations as OP, Pipe\Initial, PossiblyInversible,
    Reindexable, SingularOperation};
use FiiSoft\Jackdaw\Operation\LastOperation;
use FiiSoft\Jackdaw\Operation\Mapping\{AccumulateSeparate, Aggregate, Chunk, ChunkBy, Classify, ConditionalMap, Flat,
    Flip, Map, MapBy, MapFieldWhen, MapKey, MapKeyValue, MapMany, MapWhen, Reindex, Scan, Tokenize, Tuple, UnpackTuple,
    Window};
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Sending\{Feed, FeedMany, SendTo, SendToMany};
use FiiSoft\Jackdaw\Operation\Special\{CountableRead, Limit, ReadMany, ReadNext, ReadWhileUntil, Shuffle, SwapHead};
use FiiSoft\Jackdaw\Operation\Terminating\{Collect, CollectKeys, Count, Find, First, Has, HasEvery, HasOnly, IsEmpty,
    Last};
use FiiSoft\Jackdaw\Stream;

final class Pipe implements StreamBuilder, Destroyable
{
//    private static int $instanceCounter = 0;
    
    public Operation $head;
    public Operation $last;
    
    /** @var Operation[] */
    public array $stack = [];
    
    /** @var Operation[] */
    public array $heads = [];
    
    /** @var Operation|LastOperation|null  */
    private $replacement = null;
    
    private bool $isPrepared = false;
    private bool $isDestroying = false;
    private bool $isResuming = false;
    private bool $isPrototype;
    
//    private int $myNumber;
    
    private Stream $stream;
    
    private ?ChainOperationResult $chainOperationResult = null;
    private ?Operation $restartedFrom = null;
    
    public function __construct(Stream $stream, bool $isPrototype = false)
    {
//        $this->myNumber = ++self::$instanceCounter;
        
        $this->stream = $stream;
        $this->isPrototype = $isPrototype;
        
        $this->head = new Initial();
        $this->last = $this->head;
    }
    
    public function clone(): self
    {
        $copy = clone $this;
        
        if ($this->isPrototype) {
            $copy->isPrepared = false;
        }
        
        return $copy;
    }
    
    private function __clone()
    {
//        $this->myNumber = ++self::$instanceCounter;
        
        if (!empty($this->stack) || !empty($this->heads)) {
            throw PipeExceptionFactory::cannotClonePipeWithNoneEmptyStack();
        }
        
        $this->cloneAndSetHeadAndLast($this->restartedFrom ?? $this->head);
        $this->restartedFrom = null;
    }
    
    private function cloneAndSetHeadAndLast(Operation $head): void
    {
        if ($head instanceof Initial) {
            $this->head = clone $head;
        } else {
            $this->head = (clone new Initial($head))->getNext();
        }
        
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
            $this->heads = [];
        }
    }
    
    public function prepareForWrap(): void
    {
        $this->substituteWithCopyFor(Detachable::class);
    }
    
    public function prepareForJoin(): void
    {
        $this->substituteWithCopyFor(Detachable::class);
    }
    
    public function prepareForConsume(): void
    {
        $this->substituteWithCopyFor(Cache::class);
    }
    
    private function substituteWithCopyFor(string $desired): void
    {
        for ($node = $this->head; $node !== null; $node = $node->getNext()) {
            if ($node instanceof Detachable && $node instanceof $desired) {
                $this->substituteNode($node, $node->makeDetachedCopy());
            }
        }
    }
    
    private function substituteNode(Operation $node, Operation $substitute): void
    {
        $prev = $node->getPrev();
        $next = $node->getNext();
        
        $prev->setNext($substitute, true);
        $substitute->setNext($next, true);
        
        $this->last = $this->head->getLast();
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
            $this->optimizeSwapHeadOperations();
            $this->optimizeSingularOperations();
            $this->removeInitialNode();
            $this->head->prepare();
            $this->isPrepared = true;
        }
    }
    
    public function assignStream(Stream $stream): void
    {
        $this->stream = $stream;
        $this->head->assignStream($stream);
    }
    
    public function restartWith(Operation $operation): void
    {
        if ($this->isPrototype && $this->restartedFrom === null) {
            $this->restartedFrom = $this->head;
        }
        
        $this->head = $operation;
    }
    
    public function chainOperation(Operation $operation): ChainOperationResult
    {
        $canAppendResult = $this->canAppend($operation);
        
        if ($canAppendResult->canAppend) {
            if ($canAppendResult->pipe !== null) {
                $canAppendResult->pipe->append($operation);
            } else {
                $this->append($operation);
            }
            
            $operation->assignStream($this->stream);
        } elseif ($this->replacement !== null) {
            $operation = $this->replacement;
            $this->replacement = null;
            
            if ($operation instanceof Operation) {
                $operation->assignStream($this->stream);
            }
        }
        
        return new ChainOperationResult($canAppendResult->pipe ?? $this, $this->stream, $operation);
    }
    
    private function canAppend(Operation $next): CanAppendResult
    {
        if ($this->last instanceof LastOperation) {
            throw PipeExceptionFactory::cannotAddOperationToTheFinalOne();
        }
        
        if ($next instanceof StackableFilter) {
            if ($this->last instanceof FilterMany) {
                $this->last->add($next);
                return $this->cannotAppend();
            }
            if ($this->last instanceof StackableFilter) {
                $this->replaceLastOperation(new FilterMany($this->last, $next));
                return $this->cannotAppend();
            }
            if ($this->keepsItemsUnchanged($this->last)) {
                $node = $this->findPlaceForFilterMany($this->last);
                if ($node instanceof FilterMany) {
                    $node->add($next);
                } elseif ($node instanceof StackableFilter) {
                    $this->replaceNode($node, new FilterMany($node, $next));
                } else {
                    $node->getNext()->prepend($next);
                }
                return $this->cannotAppend();
            }
        } elseif ($next instanceof Map) {
            if ($next->mapper() instanceof Value) {
                return $this->cannotAppend();
            }
            if ($this->last instanceof MapKey) {
                return $this->canAppendChooseResult(
                    !($this->last->mapper() instanceof Value && $next->mapper() instanceof Key)
                );
            }
            if ($this->last instanceof Flip && $next->mapper() instanceof Key) {
                $this->removeLastNode();
                $this->insertOperations(OP::mapKey(Mappers::value()));
                return $this->cannotAppend();
            }
            if ($this->last instanceof MapMany) {
                $this->last->add($next);
                return $this->cannotAppend();
            }
            if ($this->last instanceof Map) {
                if (!$this->last->mergeWith($next)) {
                    $this->replaceLastOperation(new MapMany($this->last, $next));
                }
                return $this->cannotAppend();
            }
        } elseif ($next instanceof StackableFilterBy) {
            if ($this->last instanceof FilterByMany) {
                $this->last->add($next);
                return $this->cannotAppend();
            }
            if ($this->last instanceof StackableFilterBy) {
                $this->replaceLastOperation(new FilterByMany($this->last, $next));
                return $this->cannotAppend();
            }
            if ($this->keepsItemsUnchanged($this->last)) {
                $node = $this->findPlaceForFilterByMany($this->last);
                if ($node instanceof FilterByMany) {
                    $node->add($next);
                } else {
                    $node->getNext()->prepend(new FilterByMany($next));
                }
                return $this->cannotAppend();
            }
        } elseif ($next instanceof MapKey) {
            if ($next->mapper() instanceof Key) {
                return $this->cannotAppend();
            }
            if ($this->last instanceof Flip && $next->mapper() instanceof Value) {
                $this->removeLastNode();
                $this->insertOperations(OP::map(Mappers::key()));
                return $this->cannotAppend();
            }
            if ($this->last instanceof Map) {
                return $this->canAppendChooseResult(
                    !($this->last->mapper() instanceof Key && $next->mapper() instanceof Value)
                );
            }
            if ($this->last instanceof MapKey) {
                return $this->canAppendChooseResult(!$this->last->mergeWith($next));
            }
        } elseif ($next instanceof Limit) {
            if ($this->last instanceof Limitable) {
                if ($this->last->applyLimit($next->limit())) {
                    return $this->cannotAppend();
                }
                $this->replaceLastOperation($this->last->createWithLimit($next->limit()));
                return $this->yesCanAppend();
            }
            if ($this->last instanceof Sort) {
                $this->replaceLastOperation($this->last->createSortLimited($next->limit()));
                return $this->cannotAppend();
            }
            if ($this->last instanceof Reverse) {
                $this->removeLastNode();
                $this->insertOperations(OP::tail($next->limit()), OP::reverse());
                return $this->cannotAppend();
            }
        } elseif ($next instanceof Skip) {
            if ($this->last instanceof Skip) {
                $this->replaceLastOperation($this->last->mergeWith($next));
                return $this->cannotAppend();
            }
        } elseif ($next instanceof Reverse) {
            if ($this->last instanceof Reverse) {
                $this->removeLastNode();
                return $this->cannotAppend();
            }
            if ($this->last instanceof Shuffle) {
                return $this->cannotAppend();
            }
            if ($this->last instanceof Limitable && $this->last->limit() === 1) {
                return $this->cannotAppend();
            }
            if ($this->last instanceof Sort) {
                $this->last->reverseOrder();
                return $this->cannotAppend();
            }
        } elseif ($next instanceof Reindex) {
            if ($this->last instanceof Reindex) {
                $this->last->mergeWith($next);
                return $this->cannotAppend();
            }
            if ($this->last instanceof MapKey) {
                $this->removeLastNode();
            } elseif ($next->isDefaultReindex()) {
                if ($this->last instanceof Gather
                    || $this->last instanceof AccumulateSeparate
                    || $this->last instanceof Aggregate
                    || $this->last instanceof Chunk
                    || $this->last instanceof Segregate
                    || $this->last instanceof Tokenize
                    || $this->last instanceof Tuple
                ) {
                    return $this->cannotAppend();
                }
            }
        } elseif ($next instanceof Flip) {
            if ($this->last instanceof Flip) {
                $this->removeLastNode();
                return $this->cannotAppend();
            }
            if ($this->last instanceof Map && $this->last->mapper() instanceof Key) {
                $this->removeLastNode();
                $this->insertOperations(OP::map(Mappers::key()));
                return $this->cannotAppend();
            }
            if ($this->last instanceof MapKey && $this->last->mapper() instanceof Value) {
                $this->removeLastNode();
                $this->chainOperation(OP::mapKey(Mappers::value()));
                return $this->cannotAppend();
            }
        } elseif ($next instanceof Shuffle) {
            if ($this->last instanceof Shuffle) {
                $this->replaceLastOperation($this->last->mergedWith($next));
                return $this->cannotAppend();
            }
            if ($next instanceof ShuffleAll) {
                if ($this->last instanceof Reverse || $this->last instanceof Sort) {
                    $this->removeLastNode();
                }
            }
            if ($this->last instanceof Limitable && $this->last->limit() === 1) {
                return $this->cannotAppend();
            }
        } elseif ($next instanceof Tail) {
            if ($this->last instanceof Tail) {
                $this->last->mergeWith($next);
                return $this->cannotAppend();
            }
            if ($this->last instanceof Sort) {
                $this->last->reverseOrder();
                $this->insertOperations(OP::limit($next->length()), OP::reverse());
                return $this->cannotAppend();
            }
            if ($this->last instanceof Limitable) {
                if ($this->last->limit() > $next->length()) {
                    $this->insertOperations(OP::skip($this->last->limit() - $next->length()));
                }
                return $this->cannotAppend();
            }
            if ($this->last instanceof Reverse) {
                $this->removeLastNode();
                $this->insertOperations(OP::limit($next->length()), OP::reverse());
                return $this->cannotAppend();
            }
        } elseif ($next instanceof Flat) {
            if ($this->last instanceof Flat) {
                $this->last->mergeWith($next);
                return $this->cannotAppend();
            }
            if ($this->last instanceof Map) {
                $mapper = $this->last->mapper();
                if ($mapper instanceof TokenizeMapper) {
                    $this->removeLastNode();
                    $this->insertOperations(OP::tokenize($mapper->tokens()));
                    return $this->cannotAppend();
                }
            }
            if ($this->last instanceof Gather) {
                $reindex = $this->last->isReindexed();
                $this->removeLastNode();
                if ($reindex) {
                    $this->insertOperations(OP::reindex());
                }
                if ($next->isLevel(1)) {
                    return $this->cannotAppend();
                }
                if (!$next->isLevel(0)) {
                    $next->decreaseLevel();
                }
            }
            if ($this->last instanceof Chunk && !$this->last->isReindexed()) {
                $this->removeLastNode();
                return $this->canAppendChooseResult(!$next->isLevel(1));
            }
        } elseif ($next instanceof SendTo) {
            if ($this->last instanceof SendTo) {
                $this->replaceLastOperation($this->last->createSendToMany($next));
                return $this->cannotAppend();
            }
            if ($this->last instanceof SendToMany) {
                $this->last->addConsumers($next->consumer());
                return $this->cannotAppend();
            }
        } elseif ($next instanceof SendToMany) {
            if ($this->last instanceof SendToMany) {
                $this->last->addConsumers(...$next->getConsumers());
                return $this->cannotAppend();
            }
            if ($this->last instanceof SendTo) {
                $next->addConsumers($this->last->consumer());
                $this->removeLastNode();
            }
        } elseif ($next instanceof Sort || $next instanceof SortLimited) {
            if ($this->changesTheOrder($this->last)) {
                $this->removeLastNode();
            }
        } elseif ($next instanceof Gather) {
            if ($this->last instanceof Reindex) {
                if ($next->isReindexed()) {
                    $this->removeLastNode();
                } elseif ($this->last->isDefaultReindex()) {
                    $this->replaceLastOperation($next->reindexed());
                    return $this->cannotAppend();
                }
            }
        } elseif ($next instanceof Feed) {
            if ($this->last instanceof FeedMany) {
                $this->last->add($next);
                return $this->cannotAppend();
            }
            if ($this->last instanceof Feed) {
                $this->replaceLastOperation($this->last->createFeedMany($next));
                return $this->cannotAppend();
            }
        } elseif ($next instanceof ConditionalMap) {
            if ($next->shouldBeNonConditional()) {
                $this->insertOperations(OP::map($next->getMaper()));
                return $this->cannotAppend();
            }
            return $this->canAppendChooseResult(!$next->isBarren());
        } elseif ($next instanceof First) {
            if ($this->last instanceof Sort) {
                $this->insertOperations(OP::limit(1));
            } elseif ($this->last instanceof Limit) {
                $this->removeLastNode();
            } elseif ($this->last instanceof Limitable) {
                if (!$this->last->applyLimit(1)) {
                    $this->replaceLastOperation($this->last->createWithLimit(1));
                }
            } elseif ($this->last instanceof Reverse) {
                $this->removeLastNode();
                $this->insertLastOperation(OP::last($this->stream));
                return $this->cannotAppend();
            } elseif ($this->last instanceof FilterOp || $this->last instanceof Omit) {
                $this->replaceTerminatingOperation($this->last->createFind($this->stream));
                return $this->cannotAppend();
            }
        } elseif ($next instanceof Last) {
            if ($this->last instanceof Sort) {
                $this->last->reverseOrder();
                $this->insertLastOperation(OP::first($this->stream));
                return $this->cannotAppend();
            }
            if ($this->last instanceof Reverse) {
                $this->removeLastNode();
                $this->insertLastOperation(OP::first($this->stream));
                return $this->cannotAppend();
            }
            if ($this->last instanceof Tail) {
                $this->removeLastNode();
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
                || $this->last instanceof MapBy
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
                $this->removeLastNode();
                return $this->canAppend($next);
            }
        } elseif ($next instanceof Find
            || $next instanceof Has
            || $next instanceof HasOnly
            || $next instanceof HasEvery
        ) {
            if ($this->changesTheOrder($this->last)) {
                $this->removeLastNode();
                return $this->canAppend($next);
            }
        } elseif ($next instanceof Count) {
            if ($this->keepsQuantity($this->last)) {
                $this->removeLastNode();
                return $this->canAppend($next);
            }
        } elseif ($next instanceof Collect) {
            if ($this->last instanceof Reindex && $this->last->isDefaultReindex()) {
                $this->replaceTerminatingOperation($next->reindexed());
                return $this->cannotAppend();
            }
            if ($this->last instanceof Flip && $next->isReindexed()) {
                $this->replaceTerminatingOperation(OP::collectKeys($this->stream));
                return $this->cannotAppend();
            }
        } elseif ($next instanceof CollectKeys) {
            if ($this->last instanceof Flip) {
                $this->replaceTerminatingOperation(OP::collect($this->stream, true));
                return $this->cannotAppend();
            }
        } elseif ($next instanceof UnpackTuple) {
            if ($this->last instanceof Tuple && $next->isAssoc() === $this->last->isAssoc()) {
                $this->removeLastNode();
                return $this->cannotAppend();
            }
            if ($this->last instanceof Reindex || $this->last instanceof MapKey) {
                $this->removeLastNode();
            }
        } elseif ($next instanceof Tuple) {
            if ($this->last instanceof UnpackTuple && $next->isAssoc() === $this->last->isAssoc()) {
                $this->removeLastNode();
                return $this->cannotAppend();
            }
        } elseif ($next instanceof PossiblyInversible) {
            $inversed = $next->createInversed();
            if ($inversed !== null) {
                $this->appendReplacement($inversed);
                return $this->cannotAppend();
            }
        } elseif ($next instanceof Window && $next->isLikeChunk()) {
            $this->insertOperations(OP::chunk($next->size(), $next->reindex()));
            return $this->cannotAppend();
        } elseif ($next instanceof EveryNth) {
            if ($next->num() === 1) {
                return $this->cannotAppend();
            }
            if ($this->last instanceof EveryNth) {
                $this->last->applyNum($next->num());
                return $this->cannotAppend();
            }
        } elseif ($next instanceof SkipNth) {
            if ($next->num() === 2) {
                if ($this->last instanceof SkipNth && $this->last->num() === 3) {
                    $this->removeLastNode();
                    $this->insertOperations(OP::everyNth(3));
                } else {
                    $this->insertOperations(OP::everyNth(2));
                }
                return $this->cannotAppend();
            }
        } elseif ($next instanceof CountableRead) {
            if ($next->howManyIsConstantZero()) {
                return $this->cannotAppend();
            }
            if ($next instanceof ReadNext) {
                if ($this->last instanceof ReadNext) {
                    $this->last->mergeWith($next);
                    return $this->cannotAppend();
                }
            } elseif ($next instanceof ReadMany && $next->howManyIsConstantOne()) {
                $this->insertOperations(OP::readNext());
                if ($next->reindexKeys()) {
                    $this->insertOperations(OP::mapKey(0));
                }
                return $this->cannotAppend();
            }
        } elseif ($next instanceof Cache) {
            if ($this->last instanceof Cache) {
                return $this->cannotAppend();
            }
            if ($this->isCollectingOperation($this->last)) {
                $this->appendReplacement($next->forceCollectingData());
                return $this->cannotAppend();
            }
        }
        
        if ($next instanceof Reindexable && $this->last instanceof Reindex && $next->isReindexed()) {
            $this->removeLastNode();
        }
        
        return $this->yesCanAppend();
    }
    
    private function yesCanAppend(): CanAppendResult
    {
        return $this->canAppendChooseResult(true);
    }
    
    private function cannotAppend(): CanAppendResult
    {
        return $this->canAppendChooseResult(false);
    }
    
    private function canAppendChooseResult(bool $canAppend): CanAppendResult
    {
        if ($this->chainOperationResult !== null) {
            $canAppendResult = $this->chainOperationResult->createCanAppendResult($canAppend);
            $this->chainOperationResult = null;
        } else {
            $canAppendResult = new CanAppendResult($canAppend);
        }
        
        return $canAppendResult;
    }
    
    private function insertLastOperation(Operation $operation): void
    {
        $this->insertOperations($operation);
        $this->replacement = $this->chainOperationResult->operation;
    }
    
    private function insertOperations(Operation ...$operations): void
    {
        $instance = $this->isPrototype ? clone $this : $this;
        
        foreach ($operations as $operation) {
            $this->chainOperationResult = $instance->chainOperation($operation);
            $instance = $this->chainOperationResult->pipe;
        }
    }
    
    private function appendReplacement(Operation $operation): void
    {
        $this->replacement = $operation;
        $this->append($operation);
    }
    
    private function replaceTerminatingOperation(Operation $operation): void
    {
        $this->replaceLastOperation($operation);
        $this->replacement = $operation;
    }
    
    private function replaceLastOperation(Operation $operation): void
    {
        $this->removeLastNode();
        $this->append($operation);
    }
    
    public function append(Operation $operation): void
    {
        $this->last = $this->last->setNext($operation);
    }
    
    private function removeInitialNode(): void
    {
        if ($this->head instanceof Initial) {
            $this->head = $this->head->removeFromChain();
        }
    }
    
    private function removeLastNode(): void
    {
        $this->last = $this->last->removeFromChain();
    }
    
    private function isCollectingOperation(Operation $operation): bool
    {
        return $operation instanceof Categorize
            || $operation instanceof Fork
            || $operation instanceof ForkMatch
            || $operation instanceof Gather
            || $operation instanceof Reverse
            || $operation instanceof Segregate
            || $operation instanceof ShuffleAll
            || $operation instanceof Sort
            || $operation instanceof SortLimited
            || $operation instanceof Tail;
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
        return $operation instanceof StackableFilterBy || $operation instanceof FilterByMany;
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
        return $operation instanceof StackableFilter || $operation instanceof FilterMany;
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
    
    private function optimizeSingularOperations(): void
    {
        for ($node = $this->head->getNext(); $node !== null; $node = $node->getNext()) {
            if ($node instanceof SingularOperation && $node->isSingular()) {
                $this->replaceNode($node, $node->getSingular());
            }
        }
    }
    
    private function optimizeSwapHeadOperations(): void
    {
        $first = $this->head->getNext();
        
        if ($first instanceof ReadNext && $first->howManyIsConstant()) {
            $this->replaceNode($first,
                OP::skip($first->getHowMany()),
                OP::everyNth($first->getHowMany() + 1)
            );
        }
        
        if ($first instanceof ReadMany && $first->howManyIsConstant()) {
            $this->replaceNode($first,
                OP::skip(1),
                OP::window($first->getHowMany(), $first->getHowMany() + 1, $first->reindexKeys()),
                OP::flat(1)
            );
        }
        
        if ($first instanceof ReadWhileUntil && $first->preserveKeys()) {
            $this->replaceNode($first,
                OP::skip(1),
                $first->createFilterOperation()
            );
        }
    }
    
    private function replaceNode(Operation $node, Operation ...$operations): void
    {
        $head = $tail = null;
        
        foreach ($operations as $operation) {
            if ($head === null) {
                $head = $operation;
                $tail = $head;
            } else {
                $tail = $tail->setNext($operation);
            }
        }
        
        $prev = $node->getPrev();
        $next = $node->getNext();
        
        if ($prev !== null) {
            $prev->setNext($head, true);
        }
        
        if ($next !== null) {
            $tail->setNext($next, true);
        }
        
        $this->last = $this->head->getLast();
    }
    
    public function containsSwapOperation(): bool
    {
        for ($node = $this->head; $node !== null; $node = $node->getNext()) {
            if ($node instanceof SwapHead) {
                return true;
            }
        }
        
        return false;
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