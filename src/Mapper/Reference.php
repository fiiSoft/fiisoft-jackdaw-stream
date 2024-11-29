<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Reference extends StateMapper
{
    /** @var mixed REFERENCE */
    private $variable;
    
    /**
     * @param mixed $variable REFERENCE
     */
    public function __construct(&$variable)
    {
        $this->variable = &$variable;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return $this->variable;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $_) {
            yield $key => $this->variable;
        }
    }
    
    protected function buildKeyMapper(iterable $stream): iterable
    {
        foreach ($stream as $value) {
            yield $this->variable => $value;
        }
    }
}