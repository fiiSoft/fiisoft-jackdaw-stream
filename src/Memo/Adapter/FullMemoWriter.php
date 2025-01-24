<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Memo\Adapter;

use FiiSoft\Jackdaw\Memo\Adapter\TupleReader\KeyReader;
use FiiSoft\Jackdaw\Memo\Adapter\TupleReader\ValueReader;
use FiiSoft\Jackdaw\Memo\FullMemo;
use FiiSoft\Jackdaw\Memo\MemoReader;

final class FullMemoWriter implements FullMemo
{
    private TupleItem $data;
    
    private ?ValueReader $valueReader = null;
    private ?KeyReader $keyReader = null;
    
    public function __construct()
    {
        $this->data = new TupleItem();
    }
    
    /**
     * @inheritDoc
     */
    public function write($value, $key): void
    {
        $this->data->value = $value;
        $this->data->key = $key;
    }
    
    public function value(): MemoReader
    {
        if ($this->valueReader === null) {
            $this->valueReader = new ValueReader($this->data);
        }
        
        return $this->valueReader;
    }
    
    public function key(): MemoReader
    {
        if ($this->keyReader === null) {
            $this->keyReader = new KeyReader($this->data);
        }
        
        return $this->keyReader;
    }
    
    public function tuple(): MemoReader
    {
        return $this->data;
    }
}