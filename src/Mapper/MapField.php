<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class MapField extends StateMapper
{
    private Mapper $mapper;
    
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     */
    public function __construct($field, Mapper $mapper)
    {
        $this->field = Helper::validField($field, 'field');
        $this->mapper = $mapper;
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
    {
        $value[$this->field] = $this->mapper->map($value[$this->field], $key);
        
        return $value;
    }
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $value[$this->field] = $this->mapper->map($value[$this->field], $key);
            
            yield $key => $value;
        }
    }
}