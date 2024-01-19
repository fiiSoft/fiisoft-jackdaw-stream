<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Adapter;

use FiiSoft\Jackdaw\Producer\Tech\BaseProducer;

final class ReferenceAdapter extends BaseProducer
{
    /** @var mixed */
    private $variable;
    
    private int $index = 0;
    
    /**
     * @param mixed $variable REFERENCE
     */
    public function __construct(&$variable)
    {
        $this->variable = &$variable;
    }
    
    public function getIterator(): \Generator
    {
        while ($this->variable !== null) {
            yield $this->index++ => $this->variable;
        }
    }
}