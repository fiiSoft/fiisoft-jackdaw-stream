<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited;

use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\Buffer\CircularEntryBufferFull;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\Buffer\CircularEntryBufferNotFull;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\Limited\Buffer\SingleEntryBuffer;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\LimitedSequenceMemo;
use FiiSoft\Jackdaw\Memo\Entry;

final class EntryBufferFactory
{
    public static function initial(LimitedSequenceMemo $client, int $size): EntryBuffer
    {
        return $size === 1
            ? new SingleEntryBuffer()
            : self::notFull($client, new \SplFixedArray($size));
    }
    
    /**
     * @param \SplFixedArray<Entry> $buffer
     */
    public static function notFull(LimitedSequenceMemo $client, \SplFixedArray $buffer, int $index = 0): EntryBuffer
    {
        return new CircularEntryBufferNotFull($client, $buffer, $index);
    }
    
    /**
     * @param \SplFixedArray<Entry> $buffer
     */
    public static function full(LimitedSequenceMemo $client, \SplFixedArray $buffer): EntryBuffer
    {
        return new CircularEntryBufferFull($client, $buffer);
    }
}