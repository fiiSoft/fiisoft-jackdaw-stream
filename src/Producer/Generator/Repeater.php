<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Producer\Tech\LimitedProducer;

final class Repeater extends LimitedProducer
{
    /** @var mixed */
    private $value;
    
    /**
     * @param mixed $value
     */
    public function __construct($value, int $limit = \PHP_INT_MAX)
    {
        parent::__construct($limit);
        
        $this->value = $value;
    }
    
    public function getIterator(): \Generator
    {
        $count = -1;
        $limit = $this->limit - 1;
        
        while ($count !== $limit) {
            yield ++$count => $this->value;
        }
    }
}