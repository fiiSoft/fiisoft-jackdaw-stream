<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Internal\Result;
use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Internal\ResultProvider;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\SourceAware;
use FiiSoft\Jackdaw\Internal\StreamPipe;
use FiiSoft\Jackdaw\Operation\Internal\CommonOperationCode;
use FiiSoft\Jackdaw\Operation\LastOperation;
use FiiSoft\Jackdaw\Operation\Operation;
use FiiSoft\Jackdaw\Stream;

abstract class FinalOperation extends StreamPipe implements LastOperation, Operation, ResultProvider, SourceAware
{
    use CommonOperationCode { destroy as commonDestroy; resume as commonResume; }
    
    private Stream $stream;
    private ?Result $result = null;
    
    /** @var Stream[] */
    private array $parents = [];
    
    /** @var callable|mixed|null */
    private $orElse;
    
    private bool $isCloning = false;
    private bool $refreshResult = false;
    private bool $isResuming = false;
    
    /**
     * @param callable|mixed|null $orElse
     */
    public function __construct(Stream $stream, $orElse = null)
    {
        $this->stream = $stream;
        $this->orElse = $orElse;
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
    final public function transform($transformer): ResultApi
    {
        return $this->result()->transform($transformer);
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
    
    final public function getIterator(): \Iterator
    {
        return $this->result()->getIterator();
    }
    
    private function result(): Result
    {
        if ($this->result === null) {
            $this->result = new Result($this->stream, $this, $this->orElse, $this->parents);
        } elseif ($this->refreshResult) {
            $this->result->refreshResult();
            $this->refreshResult = false;
        }
        
        return $this->result;
    }
    
    protected function __clone()
    {
        $this->result = null;
        $this->refreshResult = false;
        $this->isResuming = false;
        
        if ($this->next !== null) {
            $this->next = clone $this->next;
            $this->next->setPrev($this);
        }
    }
    
    final protected function cloneStream(): Stream
    {
        \assert($this->result === null && !$this->isCloning, 'Invalid cloning of FinalOperation');
        
        $this->isCloning = true;
        $streamCopy = $this->stream->cloneStream();
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
        $this->parents[\spl_object_id($stream)] = $stream;
    }
    
    /**
     * @inheritDoc
     */
    final public function wrap($producer): LastOperation
    {
        return $this->stream->wrap($producer)->getFinalOperation();
    }
    
    /**
     * @inheritDoc
     */
    final public function consume($producer): void
    {
        $this->stream->consume($producer);
    }
    
    final public function streamingFinished(Signal $signal): bool
    {
        return $this->next->streamingFinished($signal);
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
    
    protected function finish(): void
    {
        $this->stream->finish();
    }
}