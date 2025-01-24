<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

use FiiSoft\Jackdaw\Memo\Adapter\FullMemoWriter;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\InfiniteSequenceMemo;
use FiiSoft\Jackdaw\Memo\Adapter\Sequence\LimitedSequenceMemo;
use FiiSoft\Jackdaw\Memo\Adapter\SingleMemo\KeyMemo;
use FiiSoft\Jackdaw\Memo\Adapter\SingleMemo\ValueMemo;

final class Memo
{
    /**
     * @param mixed|null $initial
     */
    public static function value($initial = null): SingleMemo
    {
        return new ValueMemo($initial);
    }
    
    /**
     * @param mixed|null $initial
     */
    public static function key($initial = null): SingleMemo
    {
        return new KeyMemo($initial);
    }
    
    public static function full(): FullMemo
    {
        return new FullMemoWriter();
    }
    
    public static function sequence(?int $length = null): SequenceMemo
    {
        return $length !== null ? new LimitedSequenceMemo($length) : new InfiniteSequenceMemo();
    }
}