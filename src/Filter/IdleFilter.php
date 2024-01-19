<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

final class IdleFilter extends BaseFilter
{
    private bool $result;
    
    public static function true(?int $mode = null): self
    {
        return new self(true, $mode);
    }
    
    public static function false(?int $mode = null): self
    {
        return new self(false, $mode);
    }
    
    protected function __construct(bool $result, ?int $mode)
    {
        parent::__construct($mode);
        
        $this->result = $result;
    }
    
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return $this->result;
    }
    
    public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode ? new self($this->result, $mode) : $this;
    }
    
    public function negate(): Filter
    {
        return new self(!$this->result, $this->mode);
    }
    
    public function buildStream(iterable $stream): iterable
    {
        if ($this->result) {
            yield from $stream;
        } else {
            yield from [];
        }
    }
}