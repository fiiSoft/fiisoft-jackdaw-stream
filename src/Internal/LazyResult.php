<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Stream;

final class LazyResult extends BaseStreamPipe implements Result
{
    /** @var Stream */
    private $stream;
    
    /** @var ResultProvider */
    private $resultProvider;
    
    /** @var Result */
    private $resultItem;
    
    /** @var bool */
    private $isExecuted = false;
    
    /** @var mixed|null */
    private $orElse;
    
    public function __construct(Stream $stream, ResultProvider $resultProvider, $orElse = null)
    {
        $this->stream = $stream;
        $this->resultProvider = $resultProvider;
        $this->orElse = $orElse;
    }
    
    public function run()
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
    public function key()
    {
        return $this->execute()->key();
    }
    
    /**
     * @inheritDoc
     */
    public function toString(): string
    {
        return $this->execute()->toString();
    }
    
    /**
     * @inheritDoc
     */
    public function toJson(): string
    {
        return $this->execute()->toJson();
    }
    
    /**
     * @inheritDoc
     */
    public function toJsonAssoc(): string
    {
        return $this->execute()->toJsonAssoc();
    }
    
    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->execute()->toArray();
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
    public function call($consumer)
    {
        $this->execute()->call($consumer);
    }
    
    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->execute()->toString();
    }
    
    private function execute(): Result
    {
        if (!$this->isExecuted) {
            $this->stream->run();
    
            if ($this->resultProvider->hasResult()) {
                $this->resultItem = ResultItem::createFound($this->resultProvider->getResult());
            } else {
                $this->resultItem = ResultItem::createNotFound($this->orElse);
            }
            
            $this->isExecuted = true;
        }
        
        return $this->resultItem;
    }
    
    protected function sendTo(BaseStreamPipe $stream): bool
    {
        return $this->stream->sendTo($stream);
    }
    
    protected function processExternalPush(Stream $sender): bool
    {
        return $this->stream->processExternalPush($sender);
    }
    
    protected function continueIteration(bool $once = false): bool
    {
        return $this->stream->continueIteration($once);
    }
}