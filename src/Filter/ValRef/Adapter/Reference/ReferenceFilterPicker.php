<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\ValRef\Adapter\Reference;

use FiiSoft\Jackdaw\Filter\ValRef\FilterAdapterFactory;
use FiiSoft\Jackdaw\Filter\ValRef\FilterPicker;

final class ReferenceFilterPicker extends FilterPicker
{
    /** @var mixed REFERENCE */
    private $variable;
    
    /**
     * @param mixed $variable REFERENCE
     */
    public function __construct(&$variable, bool $isNot = false)
    {
        parent::__construct($isNot);
        
        $this->variable = &$variable;
    }
    
    protected function createFactory(): FilterAdapterFactory
    {
        return new ReferenceFilterFactory($this->variable, $this);
    }
}