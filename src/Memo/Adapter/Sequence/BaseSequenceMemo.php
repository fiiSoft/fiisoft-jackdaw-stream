<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader\KeyReader;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader\PairReader;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader\TupleReader;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Reader\ValueReader;
use FiiSoft\Jackdaw\Memo\MemoReader;
use FiiSoft\Jackdaw\Memo\Sequence\Inspector\SequenceCallableInspector;
use FiiSoft\Jackdaw\Memo\Sequence\Inspector\SequenceObjectInspector;
use FiiSoft\Jackdaw\Memo\Sequence\Matcher\SequenceMatcherPredicate;
use FiiSoft\Jackdaw\Memo\SequenceInspector;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Memo\SequencePredicate;
use FiiSoft\Jackdaw\Stream;

abstract class BaseSequenceMemo implements SequenceMemo
{
    /** @var MemoReader[] */
    private array $keyReaders = [];
    
    /** @var MemoReader[] */
    private array $valueReaders = [];
    
    /** @var MemoReader[] */
    private array $tupleReaders = [];
    
    /** @var MemoReader[] */
    private array $pairReaders = [];
    
    /**
     * @inheritDoc
     */
    final public function matches(array $pattern, $matcher = null): SequencePredicate
    {
        return new SequenceMatcherPredicate($this, $pattern, $matcher);
    }
    
    /**
     * @inheritDoc
     */
    final public function inspect($inspector): SequencePredicate
    {
        if (\is_callable($inspector)) {
            return new SequenceCallableInspector($this, $inspector);
        }
        
        if ($inspector instanceof SequenceInspector) {
            return new SequenceObjectInspector($this, $inspector);
        }
        
        throw InvalidParamException::describe('inspector', $inspector);
    }
    
    final public function key(int $index): MemoReader
    {
        if (!isset($this->keyReaders[$index])) {
            $this->keyReaders[$index] = new KeyReader($this, $index);
        }
        
        return $this->keyReaders[$index];
    }
    
    final public function value(int $index): MemoReader
    {
        if (!isset($this->valueReaders[$index])) {
            $this->valueReaders[$index] = new ValueReader($this, $index);
        }
        
        return $this->valueReaders[$index];
    }
    
    final public function tuple(int $index): MemoReader
    {
        if (!isset($this->tupleReaders[$index])) {
            $this->tupleReaders[$index] = new TupleReader($this, $index);
        }
        
        return $this->tupleReaders[$index];
    }
    
    final public function pair(int $index): MemoReader
    {
        if (!isset($this->pairReaders[$index])) {
            $this->pairReaders[$index] = new PairReader($this, $index);
        }
        
        return $this->pairReaders[$index];
    }
    
    final public function stream(): Stream
    {
        return Stream::from($this);
    }
}