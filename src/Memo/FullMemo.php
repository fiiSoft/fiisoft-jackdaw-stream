<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo;

interface FullMemo extends MemoWriter
{
    public function value(): MemoReader;
    
    public function key(): MemoReader;
    
    public function tuple(): MemoReader;
}