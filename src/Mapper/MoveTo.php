<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;

final class MoveTo implements Mapper
{
    /** @var string|int */
    private $field;
    
    /**
     * @param string|int $field
     */
    public function __construct($field)
    {
        if (Helper::isFieldValid($field)) {
            $this->field = $field;
        } else {
            throw new \InvalidArgumentException('Invalid param field');
        }
    }
    
    public function map($value, $key)
    {
        return [$this->field => $value];
    }
}