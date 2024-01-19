<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Mapper;

use FiiSoft\Jackdaw\Mapper\Extract\MultiExtract;
use FiiSoft\Jackdaw\Mapper\Extract\SingleExtract;
use FiiSoft\Jackdaw\Mapper\Internal\StateMapper;

abstract class Extract extends StateMapper
{
    /** @var mixed|null */
    protected $orElse;
    
    /**
     * @param array|string|int $fields
     * @param mixed|null $orElse
     */
    final public static function create($fields, $orElse = null): self
    {
        return \is_array($fields)
            ? new MultiExtract($fields, $orElse)
            : new SingleExtract($fields, $orElse);
    }
    
    /**
     * @param mixed|null $orElse
     */
    protected function __construct($orElse = null)
    {
        $this->orElse = $orElse;
    }
}