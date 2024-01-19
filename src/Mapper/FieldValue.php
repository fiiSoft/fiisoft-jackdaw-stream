<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class FieldValue extends StateMapper
{
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     */
    public function __construct($field)
    {
        $this->field = Helper::validField($field, 'field');
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        return $value[$this->field];
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            yield $key => $value[$this->field];
        }
    }
    
    public function mergeWith(Mapper $other): bool
    {
        if ($other instanceof self) {
            $this->field = $other->field;
            return true;
        }
        
        return false;
    }
}