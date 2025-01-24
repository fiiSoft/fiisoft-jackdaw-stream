<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Memo\Entry;

final class LimitedSequenceMemo extends BaseSequenceMemo
{
    private int $length;
    
    public function __construct(int $length)
    {
        parent::__construct();
        
        if ($length < 2) {
            throw InvalidParamException::describe('length', $length);
        }
        
        $this->length = $length;
    }
    
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        if (\count($this->sequence->entries) === $this->length) {
            \array_shift($this->sequence->entries);
        }
        
        $this->sequence->entries[] = new Entry($key, $value);
    }
    
    public function isFull(): bool
    {
        return \count($this->sequence->entries) === $this->length;
    }
}