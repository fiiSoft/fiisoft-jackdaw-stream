<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Reducer\Reducer;

final class Append extends BaseMapper
{
    private Mapper $mapper;
    
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     * @param Mapper|Reducer|Predicate|Filter|Discriminator|callable|array|mixed $mapper
     */
    public function __construct($field, $mapper)
    {
        if (Helper::isFieldValid($field)) {
            $this->field = $field;
        } else {
            throw new \InvalidArgumentException('Invalid param field');
        }
        
        $this->mapper = Mappers::getAdapter($mapper);
    }
    
    public function map($value, $key)
    {
        if (\is_array($value) || $value instanceof \ArrayAccess) {
            $value[$this->field] = $this->mapper->map($value, $key);
            return $value;
        }
    
        return [
            $key => $value,
            $this->field => $this->mapper->map($value, $key),
        ];
    }
}