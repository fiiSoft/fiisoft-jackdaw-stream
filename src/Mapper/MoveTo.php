<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;
use FiiSoft\Jackdaw\Mapper\MoveTo\MoveToFieldKey;
use FiiSoft\Jackdaw\Mapper\MoveTo\MoveToField;

abstract class MoveTo extends StateMapper
{
    /** @var string|int */
    protected $field;
    
    /**
     * @param string|int $field
     * @param string|int|null $key
     */
    final public static function create($field, $key = null): self
    {
        return $key !== null
            ? new MoveToFieldKey($field, $key)
            : new MoveToField($field);
    }
    
    /**
     * @param string|int $field
     */
    protected function __construct($field)
    {
        $this->field = Helper::validField($field, 'field');
    }
}