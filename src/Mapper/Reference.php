<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Internal\BaseMapper;

final class Reference extends BaseMapper
{
    /** @var mixed */
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
    public function map($value, $key)
    {
        return $this->variable;
    }
}