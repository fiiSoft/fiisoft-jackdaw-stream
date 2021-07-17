<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;

final class Remove implements Mapper
{
    /** @var int[]|string[] */
    private $fields;
    
    /**
     * @param array|string|int $fields
     */
    public function __construct($fields)
    {
        if (!$this->isFieldValid($fields)) {
            throw new \InvalidArgumentException('Invalid param field');
        }
    
        $this->fields = \array_flip(\is_array($fields) ? $fields : [$fields]);
    }
    
    public function map($value, $key)
    {
        if (\is_array($value)) {
            return \array_diff_key($value, $this->fields);
        }
    
        if ($value instanceof \Traversable) {
            return \array_diff_key(\iterator_to_array($value), $this->fields);
        }
    
        throw new \LogicException('Unsupported '.Helper::typeOfParam($value).' as value in Remove mapper');
    }
    
    private function isFieldValid($field): bool
    {
        return \is_scalar($field) || (\is_array($field) && !empty($field));
    }
}