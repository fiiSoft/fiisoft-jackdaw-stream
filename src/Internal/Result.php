<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Internal\DispatchReady;
use FiiSoft\Jackdaw\Operation\Terminating\FinalOperation;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\Transformer\Transformer;
use FiiSoft\Jackdaw\Transformer\Transformers;

final class Result implements ResultApi, DispatchReady
{
    private Stream $stream;
    private FinalOperation $resultProvider;
    private ?ResultItem $resultItem = null;
    private ?Transformer $transformer = null;
    
    private bool $isDestroying = false;
    
    /** @var callable|mixed|null */
    private $orElse;
    
    /** @var Stream[] */
    private array $parents;
    
    /**
     * @param callable|mixed|null $orElse
     * @param Stream[] $parents
     */
    public function __construct(
        Stream $stream,
        FinalOperation $resultProvider,
        $orElse = null,
        array $parents = []
    ) {
        $this->stream = $stream;
        $this->resultProvider = $resultProvider;
        $this->orElse = $orElse;
        $this->parents = $parents;
    }
    
    public function found(): bool
    {
        return $this->do()->found();
    }
    
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
        if ($this->resultItem === null) {
            
            foreach ($this->parents as $parent) {
                $parent->run(true);
            }
            
            $this->stream->run(true);
            
            $result = $this->resultProvider->getResult();
            
            $this->resultItem = $result !== null
                ? ResultItem::createFound($result, $this->transformer, $this->resultProvider->isReindexed())
                : ResultItem::createNotFound($this->orElse);
        }
        
        return $this->resultItem;
    }
    
    public function refreshResult(): void
    {
        $this->resultItem = null;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->orElse = null;
            $this->transformer = null;
            
            $this->stream->destroy();
            
            foreach ($this->parents as $key => $parent) {
                unset($this->parents[$key]);
                $parent->destroy();
            }
            
            if ($this->resultItem !== null) {
                $this->resultItem->destroy();
            }
            
            $this->resultProvider->destroy();
        }
    }
}