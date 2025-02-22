<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class ByArgs extends BaseFilter
{
    /** @var callable */
    private $consumer;
    
    public function __construct(callable $consumer)
    {
        parent::__construct(Check::VALUE);
        
        $this->consumer = $consumer;
    }
    
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        return ($this->consumer)(...\array_values($value));
    }
    
    /**
     * @inheritDoc
     */
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (($this->consumer)(...\array_values($value))) {
                yield $key => $value;
            }
        }
    }
    
    public function inMode(?int $mode): Filter
    {
        return $this;
    }
    
    public function negate(): Filter
    {
        return $this->createDefaultNOT(true);
    }
    
    public function equals(Filter $other): bool
    {
        return $other instanceof $this
            && $other->consumer === $this->consumer
            && parent::equals($other);
    }
}