<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Stream;
use FiiSoft\Jackdaw\Transformer\Transformer;
use FiiSoft\Jackdaw\Transformer\Transformers;

final class Result extends StreamPipe implements ResultApi, Executable
{
    private Stream $stream;
    private FinalOperation $resultProvider;
    private ?ResultItem $resultItem = null;
    private ?Transformer $transformer = null;
    
    private bool $isExecuted = false;
    private bool $isDestroying = false;
    
    /** @var callable|mixed|null */
    private $orElse;
    
    /**
     * @param callable|mixed|null $orElse
     */
    public function __construct(Stream $stream, FinalOperation $resultProvider, $orElse = null)
    {
        $this->stream = $stream;
        $this->resultProvider = $resultProvider;
        $this->orElse = $orElse;
    }
    
    public function run(): void
    {
        $this->execute();
    }
    
    /**
     * @inheritDoc
     */
    public function found(): bool
    {
        return $this->execute()->found();
    }
    
    /**
     * @inheritDoc
     */
    public function notFound(): bool
    {
        return $this->execute()->notFound();
    }
    
    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->execute()->get();
    }
    
    /**
     * @inheritDoc
     */
    public function transform($transformer): self
    {
        $this->transformer = Transformers::getAdapter($transformer);
    
        if ($this->isExecuted) {
            $this->resultItem->transform($this->transformer);
        }
        
        return $this;
    }
    
    /**
     * @inheritDoc
     */
    public function getOrElse($orElse)
    {
        return $this->execute()->getOrElse($orElse);
    }
    
    /**
     * @inheritDoc
     */
    public function key()
    {
        return $this->execute()->key();
    }
    
    /**
     * @inheritDoc
     */
    public function toString(string $separator = ','): string
    {
        return $this->execute()->toString($separator);
    }
    
    /**
     * @inheritDoc
     */
    public function toJson(int $flags = 0, bool $preserveKeys = false): string
    {
        return $this->execute()->toJson($flags, $preserveKeys);
    }
    
    /**
     * @inheritDoc
     */
    public function toJsonAssoc(int $flags = 0): string
    {
        return $this->execute()->toJsonAssoc($flags);
    }
    
    /**
     * @inheritDoc
     */
    public function toArray(bool $preserveKeys = false): array
    {
        return $this->execute()->toArray($preserveKeys);
    }
    
    /**
     * @inheritDoc
     */
    public function toArrayAssoc(): array
    {
        return $this->execute()->toArrayAssoc();
    }
    
    /**
     * @inheritDoc
     */
    public function tuple(): array
    {
        return $this->execute()->tuple();
    }
    
    /**
     * @inheritDoc
     */
    public function call($consumer): void
    {
        $this->execute()->call($consumer);
    }
    
    /**
     * @inheritDoc
     */
    public function stream(): Stream
    {
        return $this->execute()->stream();
    }
    
    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->execute()->count();
    }
    
    private function execute(): ResultItem
    {
        if (!$this->isExecuted) {
            $this->stream->run();
    
            if ($this->resultProvider->hasResult()) {
                $this->resultItem = ResultItem::createFound($this->resultProvider->getResult(), $this->transformer);
            } else {
                $this->resultItem = ResultItem::createNotFound($this->orElse);
            }
            
            $this->isExecuted = true;
        }
        
        return $this->resultItem;
    }
    
    protected function prepareSubstream(bool $isLoop): void
    {
        $this->stream->prepareSubstream($isLoop);
    }
    
    protected function process(Signal $signal): bool
    {
        return $this->stream->process($signal);
    }
    
    protected function continueIteration(bool $once = false): bool
    {
        return $this->stream->continueIteration($once);
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->isDestroying = true;
            
            $this->orElse = null;
            $this->transformer = null;
            
            $this->stream->destroy();
            
            if ($this->resultItem !== null) {
                $this->resultItem->destroy();
            }
            
            $this->resultProvider->destroy();
        }
    }
}