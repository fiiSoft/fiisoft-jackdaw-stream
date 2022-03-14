<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper\Internal;

use FiiSoft\Jackdaw\Mapper\Mapper;

abstract class CastMapper implements Mapper
{
    protected ?array $fields = null;
    protected bool $simple;
    
    /**
     * @param array|string|int|null $fields
     */
    public function __construct($fields = null)
    {
        if ($fields !== null) {
            if (\is_array($fields)) {
                if (empty($fields)) {
                    throw new \InvalidArgumentException('Param fields is invalid');
                }
                
                $this->fields = $fields;
            } else {
                $this->fields = [$fields];
            }
        }
        
        $this->simple = $this->fields === null;
    }
}