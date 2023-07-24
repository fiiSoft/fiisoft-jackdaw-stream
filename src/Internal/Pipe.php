<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Mapper\Key;
use FiiSoft\Jackdaw\Mapper\Tokenize;
use FiiSoft\Jackdaw\Mapper\Value;
use FiiSoft\Jackdaw\Operation\Chunk;
use FiiSoft\Jackdaw\Operation\ChunkBy;
use FiiSoft\Jackdaw\Operation\FilterMany;
use FiiSoft\Jackdaw\Operation\Flat;
use FiiSoft\Jackdaw\Operation\Flip;
use FiiSoft\Jackdaw\Operation\Gather;
use FiiSoft\Jackdaw\Operation\Internal\Feed;
use FiiSoft\Jackdaw\Operation\Internal\FeedMany;
use FiiSoft\Jackdaw\Operation\Internal\FilterSingle;
use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Operation\Internal\Initial;
use FiiSoft\Jackdaw\Operation\Internal\Limitable;
use FiiSoft\Jackdaw\Operation\Internal\SortingOperation;
use FiiSoft\Jackdaw\Operation\Limit;
use FiiSoft\Jackdaw\Operation\Map;
use FiiSoft\Jackdaw\Operation\MapFieldWhen;
use FiiSoft\Jackdaw\Operation\MapKey;
use FiiSoft\Jackdaw\Operation\MapKeyValue;
use FiiSoft\Jackdaw\Operation\MapMany;
use FiiSoft\Jackdaw\Operation\MapWhen;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Operation\Reindex;
use FiiSoft\Jackdaw\Operation\Reverse;
use FiiSoft\Jackdaw\Operation\Scan;
use FiiSoft\Jackdaw\Operation\SendTo;
use FiiSoft\Jackdaw\Operation\Shuffle;
use FiiSoft\Jackdaw\Operation\Skip;
use FiiSoft\Jackdaw\Operation\Sort;
use FiiSoft\Jackdaw\Operation\SortLimited;
use FiiSoft\Jackdaw\Operation\Tail;
use FiiSoft\Jackdaw\Operation\Terminating\Count;
use FiiSoft\Jackdaw\Operation\Terminating\Find;
use FiiSoft\Jackdaw\Operation\Terminating\First;
use FiiSoft\Jackdaw\Operation\Terminating\Has;
use FiiSoft\Jackdaw\Operation\Terminating\HasEvery;
use FiiSoft\Jackdaw\Operation\Terminating\HasOnly;
use FiiSoft\Jackdaw\Operation\Terminating\IsEmpty;
use FiiSoft\Jackdaw\Operation\Terminating\Last;
use FiiSoft\Jackdaw\Operation\Tuple;
use FiiSoft\Jackdaw\Operation\Unique;
use FiiSoft\Jackdaw\Stream;

final class Pipe extends ProtectedCloning
{
    public Operation $head;
    public Operation $last;
    
    /** @var Operation[] */
    public array $stack = [];
    
    private ?object $replacement = null;
    
    private bool $forceReversing = false;
    private bool $prepared = false;
    
    public function __construct()
    {
        $this->head = new Initial();
        $this->last = $this->head;
    }
    
    public function __clone()
    {
        if (!empty($this->stack)) {
            throw new \RuntimeException('Cannot clone Pipe with non-empty stack');
        }
        
        $this->head = clone $this->head;
        $this->last = $this->head->getLast();
    }
    
    public function prepare(): void
    {
        if (!$this->prepared) {
            $this->head = $this->head->removeFromChain();
            $this->prepared = true;
        }
    }
    
    public function chainOperation(Operation $operation, Stream $stream): object
    {
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
    
    public function append(Operation $operation): void
    {
        $this->last = $this->last->setNext($operation);
    }
    
    private function canAppend(Operation $next, Stream $stream): bool
    {
        if ($this->last instanceof FinalOperation) {
            throw new \LogicException('You cannot add another operation to the final one');
        }
        
        if ($next instanceof FilterSingle) {
            if ($this->last instanceof FilterMany) {
                $this->last->add($next);
                return false;
            }
            if ($this->last instanceof FilterSingle) {
                $filterMany = new FilterMany($this->last, $next);
                $this->last = $this->last->removeFromChain();
                $this->append($filterMany);
                return false;
            }
            if ($this->last instanceof Reverse
                || $this->last instanceof Shuffle
                || $this->last instanceof SortingOperation
                || $this->last instanceof Unique
            ) {
                $this->last->prepend($next);
                return false;
            }
        } elseif ($next instanceof Map) {
            if ($next->mapper() instanceof Value) {
                return false;
            }
            if ($this->last instanceof MapKey) {
                return !($this->last->mapper() instanceof Value && $next->mapper() instanceof Key);
            }
            if ($this->last instanceof MapMany) {
                $this->last->add($next);
                return false;
            }
            if ($this->last instanceof Map) {
                if (!$this->last->mergeWith($next)) {
                    $mapMany = $this->last->createMapMany($next);
                    $this->last = $this->last->removeFromChain();
                    $this->append($mapMany);
                }
                return false;
            }
        } elseif ($next instanceof Limit) {
            if ($this->last instanceof Limitable) {
                $this->last->applyLimit($next->limit());
                return false;
            }
            if ($this->last instanceof Sort) {
                $sortLimited = $this->last->createSortLimited($next->limit());
                $this->last = $this->last->removeFromChain();
                $this->append($sortLimited);
                return false;
            }
            if ($this->last instanceof Reverse) {
                $this->last = $this->last->removeFromChain();
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
                $this->last = $this->last->removeFromChain();
                return false;
            }
            if ($this->last instanceof Shuffle) {
                return false;
            }
            if ($this->last instanceof Limitable && $this->last->limit() === 1) {
                return false;
            }
            if ($this->last instanceof SortingOperation) {
                if ($this->forceReversing) {
                    $this->forceReversing = false;
                } else {
                    $this->last->reverseOrder();
                    return false;
                }
            }
        } elseif ($next instanceof Reindex) {
            if ($this->last instanceof Reindex) {
                return false;
            }
        } elseif ($next instanceof Flip) {
            if ($this->last instanceof Flip) {
                $this->last = $this->last->removeFromChain();
                return false;
            }
        } elseif ($next instanceof Shuffle) {
            if ($this->last instanceof Shuffle) {
                return false;
            }
            if ($this->last instanceof Reverse || $this->last instanceof Sort) {
                $this->last = $this->last->removeFromChain();
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
                $this->forceReversing = true;
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
                $this->last = $this->last->removeFromChain();
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
                if ($mapper instanceof Tokenize) {
                    $this->last = $this->last->removeFromChain();
                    $stream->tokenize($mapper->tokens());
                    return false;
                }
            }
            if ($this->last instanceof Gather) {
                $reindex = $this->last->isReindexed();
                $this->last = $this->last->removeFromChain();
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
        } elseif ($next instanceof SendTo) {
            if ($this->last instanceof SendTo) {
                $this->last->mergeWith($next);
                return false;
            }
        } elseif ($next instanceof SortingOperation) {
            if ($this->last instanceof Shuffle || $this->last instanceof Reverse) {
                $this->last = $this->last->removeFromChain();
            }
        } elseif ($next instanceof Gather) {
            if ($this->last instanceof Reindex) {
                $this->last = $this->last->removeFromChain();
                $next->reindex();
            }
        } elseif ($next instanceof Feed) {
            if ($this->last instanceof FeedMany) {
                $this->last->add($next);
                return false;
            }
            if ($this->last instanceof Feed) {
                $feedMany = $this->last->createFeedMany($next);
                $this->last = $this->last->removeFromChain();
                $this->append($feedMany);
                return false;
            }
        } elseif ($next instanceof MapKey) {
            if ($next->mapper() instanceof Key) {
                return false;
            }
            if ($this->last instanceof Map) {
                return !($this->last->mapper() instanceof Key && $next->mapper() instanceof Value);
            }
            if ($this->last instanceof MapKey) {
                return !$this->last->mergeWith($next);
            }
        } elseif ($next instanceof MapWhen) {
            return !$next->isBarren();
        } elseif ($next instanceof MapFieldWhen) {
            return !$next->isBarren();
        } elseif ($next instanceof First) {
            if ($this->last instanceof Sort) {
                $stream->limit(1);
            } elseif ($this->last instanceof SortLimited) {
                $this->last->applyLimit(1);
            } elseif ($this->last instanceof Reverse) {
                $this->last = $this->last->removeFromChain();
                $this->replacement = $stream->last();
                return false;
            }
        } elseif ($next instanceof Last) {
            if ($this->last instanceof Sort) {
                $this->last->reverseOrder();
                $this->replacement = $stream->first();
                return false;
            }
            if ($this->last instanceof Reverse) {
                $this->last = $this->last->removeFromChain();
                $this->replacement = $stream->first();
                return false;
            }
            if ($this->last instanceof Tail) {
                $this->last = $this->last->removeFromChain();
            }
        } elseif ($next instanceof IsEmpty) {
            if ($this->last instanceof SortingOperation
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
            ) {
                $this->last = $this->last->removeFromChain();
                return $this->canAppend($next, $stream);
            }
        } elseif ($next instanceof Find
            || $next instanceof Has|| $next instanceof HasOnly || $next instanceof HasEvery
        ) {
            if ($this->last instanceof Sort
                || $this->last instanceof Shuffle
                || $this->last instanceof Reverse
            ) {
                $this->last = $this->last->removeFromChain();
                return $this->canAppend($next, $stream);
            }
        } elseif ($next instanceof Count) {
            if ($this->last instanceof Sort
                || $this->last instanceof Shuffle
                || $this->last instanceof Reverse
                || $this->last instanceof Reindex
            ) {
                $this->last = $this->last->removeFromChain();
                return $this->canAppend($next, $stream);
            }
        } elseif ($next instanceof Unique) {
            if ($this->last instanceof Shuffle
                || $this->last instanceof Reverse
                || $this->last instanceof SortingOperation
            ) {
                $this->last->prepend($next);
                return false;
            }
        }
        
        return true;
    }
}