<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Inspector;

use FiiSoft\Jackdaw\Memo\SequenceInspector;
use FiiSoft\Jackdaw\Memo\SequenceMemo;
use FiiSoft\Jackdaw\ValueRef\IntNum;
use FiiSoft\Jackdaw\ValueRef\IntProvider;
use FiiSoft\Jackdaw\ValueRef\IntValue;

final class SequenceLengthIs implements SequenceInspector
{
    private IntValue $length;
    
    /**
     * @param IntProvider|callable|int $length
     */
    public function __construct($length)
    {
        $this->length = IntNum::getAdapter($length);
    }
    
    public function inspect(SequenceMemo $sequence): bool
    {
        return $sequence->count() === $this->length->int();
    }
    
    public function equals(SequenceInspector $other): bool
    {
        return $other === $this || $other instanceof self && $other->length->equals($this->length);
    }
}