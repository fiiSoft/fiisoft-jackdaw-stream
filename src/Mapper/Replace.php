<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Replace extends StateMapper
{
    /** @var string[]|string */
    private $search;
    
    /** @var string[]|string */
    private $replace;
    
    /**
     * It works with strings and produces strings. Internally, it's a wrapper for \str_replace.
     *
     * @param string[]|string $search
     * @param string[]|string $replace
     */
    public function __construct($search, $replace)
    {
        if (\is_string($search) && $search !== '' || \is_array($search) && $search !== []) {
            $this->search = $search;
        } else {
            throw InvalidParamException::describe('search', $search);
        }
        
        if (\is_string($replace) || \is_array($replace)) {
            $this->replace = $replace;
        } else {
            throw InvalidParamException::describe('replace', $replace);
        }
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return \str_replace($this->search, $this->replace, $value);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => \str_replace($this->search, $this->replace, $value);
        }
    }
}