<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;

final class MoveTo implements Mapper
{
    /** @var string|int */
    private $field;
    
    /** @var int|string|null */
    private $key = null;
    
    /**
     * @param string|int $field
     * @param string|int|null $key
     */
    public function __construct($field, $key = null)
    {
        if (Helper::isFieldValid($field)) {
            $this->field = $field;
        } else {
            throw new \InvalidArgumentException('Invalid param field');
        }
        
        if ($key !== null) {
            if (Helper::isFieldValid($key)) {
                $this->key = $key;
            } else {
                throw new \InvalidArgumentException('Invalid param key');
            }
        }
    }
    
    public function map($value, $key)
    {
        if ($this->key === null) {
            return [$this->field => $value];
        }
        
        return [
            $this->key  => $key,
            $this->field => $value,
        ];
    }
}