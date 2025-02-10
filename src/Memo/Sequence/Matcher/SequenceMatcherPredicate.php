<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Sequence\Matcher;

use FiiSoft\Jackdaw\Matcher\MatchBy;
use FiiSoft\Jackdaw\Matcher\Matcher;
use FiiSoft\Jackdaw\Memo\Sequence\BaseSequencePredicate;
use FiiSoft\Jackdaw\Memo\SequenceMemo;

final class SequenceMatcherPredicate extends BaseSequencePredicate
{
    private Matcher $matcher;
    
    /** @var array<string|int, mixed> */
    private array $pattern;
    
    /**
     * @param array<string|int, mixed> $pattern
     * @param Matcher|callable|null $matcher
     */
    public function __construct(SequenceMemo $sequence, array $pattern, $matcher = null)
    {
        parent::__construct($sequence);
        
        $this->matcher = MatchBy::getAdapter($matcher);
        $this->pattern = $pattern;
    }
    
    public function evaluate(): bool
    {
        if (\count($this->pattern) !== $this->sequence->count()) {
            return false;
        }
        
        $index = -1;
        
        foreach ($this->pattern as $patternKey => $patternValue) {
            $entry = $this->sequence->get(++$index);
            
            if ($this->matcher->matches($entry->value, $patternValue, $entry->key, $patternKey)) {
                continue;
            }
            
            return false;
        }
        
        return true;
    }
}