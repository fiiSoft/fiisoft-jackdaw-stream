<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Exception\InvalidParamException;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Split extends StateMapper
{
    private string $separator;
    
    public function __construct(string $separator = ' ')
    {
        if ($separator !== '') {
            $this->separator = $separator;
        } else {
            throw InvalidParamException::byName('separator');
        }
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null): array
    {
        return \explode($this->separator, $value);
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => \explode($this->separator, $value);
        }
    }
}