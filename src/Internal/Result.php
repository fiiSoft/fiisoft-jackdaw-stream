<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Terminating\FinalOperation;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\Transformer\Transformer;
use FiiSoft\Jackdaw\Transformer\Transformers;

final class Result extends StreamPipe implements ResultApi
{
    private Stream $stream;
    private ?Stream $source;
    private FinalOperation $resultProvider;
    private ?ResultItem $resultItem = null;
    private ?Transformer $transformer = null;
    
    private bool $isDestroying = false;
    private bool $createResult = true;
    
    /** @var callable|mixed|null */
    private $orElse;
    
    /**
     * @param callable|mixed|null $orElse
     */
    public function __construct(
        Stream $stream,
        FinalOperation $resultProvider,
        $orElse = null,
        ?Stream $source = null
    ) {
        $this->stream = $stream;
        $this->resultProvider = $resultProvider;
        $this->orElse = $orElse;
        $this->source = $source;
    }
    
    /**
     * @inheritDoc
     */
    public function found(): bool
    {
        return $this->do()->found();
    }
    
    /**
     * @inheritDoc
     */
    public function notFound(): bool
    {
        return $this->do()->notFound();
    }
    
    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->do()->get();
    }
    
    /**
     * @inheritDoc
     */
    public function transform($transformer): self
    {
        $this->transformer = Transformers::getAdapter($transformer);
    
        if ($this->resultItem !== null) {
            $this->resultItem->transform($this->transformer);
        }
        
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    public function getOrElse($orElse)
    {
        return $this->do()->getOrElse($orElse);
    }
    
    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->do()->key();
    }
    
    /**
     * @inheritDoc
     */
    public function toString(string $separator = ','): string
    {
        return $this->do()->toString($separator);
    }
    
    /**
     * @inheritDoc
     */
    public function toJson(?int $flags = null, bool $preserveKeys = false): string
    {
        return $this->do()->toJson($flags, $preserveKeys);
    }
    
    /**
     * @inheritDoc
     */
    public function toJsonAssoc(?int $flags = null): string
    {
        return $this->do()->toJsonAssoc($flags);
    }
    
    /**
     * @inheritDoc
     */
    public function toArray(bool $preserveKeys = false): array
    {
        return $this->do()->toArray($preserveKeys);
    }
    
    /**
     * @inheritDoc
     */
    public function toArrayAssoc(): array
    {
        return $this->do()->toArrayAssoc();
    }
    
    /**
     * @inheritDoc
     */
    public function tuple(): array
    {
        return $this->do()->tuple();
    }
    
    /**
     * @inheritDoc
     */
    public function call($consumer): void
    {
        $this->do()->call($consumer);
    }
    
    /**
     * @inheritDoc
     */
    public function stream(): Stream
    {
        return Stream::from($this);
    }
    
    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->do()->count();
    }
    
    public function getIterator(): \Iterator
    {
        return $this->do()->getIterator();
    }
    
    private function do(): ResultItem
    {
        if ($this->source !== null && $this->source->isNotStartedYet()) {
            $this->source->run();
        }
        
        if ($this->resultItem === null && $this->stream->isNotStartedYet()) {
            $this->stream->execute();
        }
        
        if ($this->createResult) {
            $this->createResultItem();
        }
        
        return $this->resultItem;
    }
    
    private function createResultItem(): void
    {
        if ($this->resultProvider->hasResult()) {
            $this->resultItem = ResultItem::createFound($this->resultProvider->getResult(), $this->transformer);
        } else {
            $this->resultItem = ResultItem::createNotFound($this->orElse);
        }
        
        $this->createResult = false;
    }
    
    protected function prepareSubstream(bool $isLoop): void
    {
        $this->stream->prepareSubstream($isLoop);
    }
    
    protected function process(Signal $signal): bool
    {
        return $this->stream->process($signal);
    }
    
    protected function refreshResult(): void
    {
        $this->createResult = true;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->orElse = null;
            $this->transformer = null;
            
            $this->stream->destroy();
            
            if ($this->source !== null) {
                $this->source->destroy();
                $this->source = null;
            }
            
            if ($this->resultItem !== null) {
                $this->resultItem->destroy();
            }
            
            $this->resultProvider->destroy();
        }
    }
}