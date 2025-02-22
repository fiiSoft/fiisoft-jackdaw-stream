<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Sequence\Matcher;

use FiiSoft\Jackdaw\Matcher\MatchBy;
use FiiSoft\Jackdaw\Matcher\Matcher;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\LimitedSequenceMemo;
use FiiSoft\Jackdaw\Memo\Entry;
use FiiSoft\Jackdaw\Memo\Sequence\BaseSequencePredicate;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\Memo\SequencePredicate;

final class SequenceMatcherPredicate extends BaseSequencePredicate
{
    private Matcher $matcher;
    
    /** @var Entry[] */
    private array $pattern = [];
    
    private int $size;
    
    private int $counter = 0;
    private ?bool $result = null;
    
    /**
     * @param array<string|int, mixed> $pattern
     * @param Matcher|callable|null $matcher
     */
    public function __construct(SequenceMemo $sequence, array $pattern, $matcher = null)
    {
        parent::__construct($sequence);
        
        $this->matcher = MatchBy::getAdapter($matcher);
        $this->size = \count($pattern);
        
        foreach ($pattern as $key => $value) {
            $this->pattern[] = new Entry($key, $value);
        }
        
        if ($this->sequence instanceof LimitedSequenceMemo) {
            $this->sequence->register($this);
        }
    }
    
    public function evaluate(): bool
    {
        if (\is_bool($this->result)) {
            return $this->result;
        }
        
        if ($this->size !== $this->sequence->count()) {
            return false;
        }
        
        $index = -1;
        
        foreach ($this->pattern as $pattern) {
            $entry = $this->sequence->get(++$index);
            
            if ($this->matcher->matches($entry->value, $pattern->value, $entry->key, $pattern->key)) {
                continue;
            }
            
            return false;
        }
        
        return true;
    }
    
    /**
     * @param mixed $value
     * @param mixed $key
     */
    public function entryAdded($value, $key): void
    {
        $pattern = $this->pattern[$this->counter];
        
        if ($this->matcher->matches($value, $pattern->value, $key, $pattern->key)) {
            if (++$this->counter === $this->size) {
                $this->result = true;
                $this->counter = 0;
            }
        } else {
            $this->counter = 0;
            $this->result = false;
        }
    }
    
    public function entryRemoved(int $index): void
    {
        $last = $this->result === true ? $this->size : $this->counter;
        $this->counter = $index;

        if ($index + 1 === $last) {
            $this->result = false;
            return;
        }
        
        $idx = $this->counter - \min($this->size, $this->sequence->count());
        
        while ($idx < 0) {
            $entry = $this->sequence->get($idx++);
            $pattern = $this->pattern[$this->counter];
            
            if ($this->matcher->matches($entry->value, $pattern->value, $entry->key, $pattern->key)) {
                ++$this->counter;
            } else {
                $this->counter = 0;
                $this->result = false;
                break;
            }
        }
    }
    
    public function sequenceCleared(): void
    {
        $this->counter = 0;
        $this->result = null;
    }
    
    public function equals(SequencePredicate $other): bool
    {
        if ($other instanceof self
            && $other->size === $this->size
            && $other->matcher->equals($this->matcher)
            && parent::equals($other)
        ) {
            for ($i = 0; $i < $this->size; ++$i) {
                if (!$this->pattern[$i]->equals($other->pattern[$i])) {
                    return false;
                }
            }
            
            return true;
        }
        
        return false;
    }
}