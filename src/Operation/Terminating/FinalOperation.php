<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Result;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Operation\Internal\CommonOperationCode;
use FiiSoft\Jackdaw\Operation\LastOperation;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\Transformer\Transformer;
use FiiSoft\Jackdaw\Transformer\Transformers;

abstract class FinalOperation extends LastOperation implements Operation, ResultProvider
{
    use CommonOperationCode { destroy as commonDestroy; resume as commonResume; }
    
    protected Stream $stream;
    protected ?Result $result = null;
    protected ?Transformer $transformer = null;
    
    /** @var callable|mixed|null */
    protected $orElse;
    
    /** @var Stream[] */
    private array $parents = [];
    
    private bool $isCloning = false;
    private bool $refreshResult = false;
    private bool $isResuming = false;
    private bool $isSettingTransformer = false;
    
    /**
     * @param callable|mixed|null $orElse
     */
    public function __construct(Stream $stream, $orElse = null, ?Transformer $transformer = null)
    {
        $this->stream = $stream;
        $this->orElse = $orElse;
        $this->transformer = $transformer;
    }
    
    final public function found(): bool
    {
        return $this->result()->found();
    }
    
    final public function notFound(): bool
    {
        return $this->result()->notFound();
    }
    
    /**
     * @inheritDoc
     */
    final public function get()
    {
        return $this->result()->get();
    }
    
    /**
     * @inheritDoc
     */
    final public function transform($transformer): LastOperation
    {
        $transformer = Transformers::getAdapter($transformer);
        
        if ($this->isSettingTransformer) {
            $this->isSettingTransformer = false;
        } elseif ($this->stream->isPrototype()) {
            $this->isSettingTransformer = true;
            $copy = $this->stream->cloneStream()->getLastOperation();
            $copy->transform($transformer);
            $this->isSettingTransformer = false;
            
            return $copy;
        }
        
        $this->transformer = $transformer;
        $this->result()->transform($this->transformer);
        
        return $this;
    }
    
    protected function __clone()
    {
        if ($this->isSettingTransformer) {
            if ($this->result !== null) {
                $this->result = clone $this->result;
            }
        } else {
            $this->result = null;
        }
        
        $this->refreshResult = false;
        $this->isResuming = false;
        
        if ($this->next !== null) {
            $this->next = clone $this->next;
            $this->next->setPrev($this);
        }
    }
    
    /**
     * @inheritDoc
     */
    final public function getOrElse($orElse)
    {
        return $this->result()->getOrElse($orElse);
    }
    
    /**
     * @inheritDoc
     */
    final public function key()
    {
        return $this->result()->key();
    }
    
    final public function tuple(): array
    {
        return $this->result()->tuple();
    }
    
    /**
     * @inheritDoc
     */
    final public function call($consumer): void
    {
        $this->result()->call($consumer);
    }
    
    final public function stream(): Stream
    {
        return $this->result()->stream();
    }
    
    final public function toString(string $separator = ','): string
    {
        return $this->result()->toString($separator);
    }
    
    final public function toJson(?int $flags = null, bool $preserveKeys = false): string
    {
        return $this->result()->toJson($flags, $preserveKeys);
    }
    
    final public function toJsonAssoc(?int $flags = null): string
    {
        return $this->result()->toJsonAssoc($flags);
    }
    
    final public function toArray(bool $preserveKeys = false): array
    {
        return $this->result()->toArray($preserveKeys);
    }
    
    final public function toArrayAssoc(): array
    {
        return $this->result()->toArrayAssoc();
    }
    
    final public function count(): int
    {
        return $this->result()->count();
    }
    
    public function getIterator(): \Iterator
    {
        return $this->result()->getIterator();
    }
    
    private function result(): Result
    {
        if ($this->result === null) {
            $this->result = new Result($this->stream, $this, $this->orElse, $this->parents, $this->transformer);
        } elseif ($this->refreshResult) {
            $this->result->refreshResult();
            $this->refreshResult = false;
        }
        
        return $this->result;
    }
    
    final protected function cloneForFork(): Stream
    {
        \assert($this->result === null && !$this->isCloning, 'Invalid cloning of FinalOperation');
        
        $this->isCloning = true;
        $streamCopy = $this->stream->cloneForFork();
        $this->isCloning = false;
        
        return $streamCopy;
    }
    
    final public function assignStream(Stream $stream): void
    {
        $this->stream = $stream;
        
        $this->next->assignStream($stream);
    }
    
    final public function assignSource(Stream $stream): void
    {
        if (!$stream->isPrototype()) {
            $this->parents[\spl_object_id($stream)] = $stream;
        }
    }
    
    /**
     * @inheritDoc
     */
    final public function wrap($producer): LastOperation
    {
        return $this->stream->wrap($producer)->getLastOperation();
    }
    
    /**
     * @inheritDoc
     */
    final public function consume($producer): void
    {
        $this->stream->consume($producer);
    }
    
    final protected function prepareSubstream(bool $isLoop): void
    {
        $this->stream->prepareSubstream($isLoop);
    }
    
    final protected function process(Signal $signal): bool
    {
        $this->refreshResult = true;
        
        return $this->stream->process($signal);
    }
    
    final protected function continueIteration(bool $once = false): bool
    {
        return $this->stream->continueIteration($once);
    }
    
    final public function resume(): void
    {
        if (!$this->isResuming) {
            $this->isResuming = true;
            try {
                $this->stream->resume();
            } finally {
                $this->isResuming = false;
            }
        }
    }
    
    final public function finish(): void
    {
        $this->stream->finish();
    }
    
    final public function refreshResult(): void
    {
        $this->refreshResult = true;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->commonDestroy();
            
            $this->orElse = null;
            
            if ($this->result !== null) {
                $this->result->destroy();
            }
            
            $this->stream->destroy();
        }
    }
    
    abstract public function isReindexed(): bool;
    
    abstract public function getResult(): ?Item;
}