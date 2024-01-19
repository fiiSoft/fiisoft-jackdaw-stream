<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

final class Append extends StateMapper
{
    private Mapper $mapper;
    
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     * @param MapperReady|callable|iterable|mixed $mapper
     */
    public function __construct($field, $mapper)
    {
        $this->field = Helper::validField($field, 'field');
        $this->mapper = Mappers::getAdapter($mapper);
    }
    
    /**
     * @inheritDoc
     */
    public function map($value, $key = null)
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
    
    protected function buildValueMapper(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            if (\is_array($value) || $value instanceof \ArrayAccess) {
                $value[$this->field] = $this->mapper->map($value, $key);
            } else {
                $value = [
                    $key => $value,
                    $this->field => $this->mapper->map($value, $key),
                ];
            }
            
            yield $key => $value;
        }
    }
}