<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator;

use FiiSoft\Jackdaw\Producer\Tech\LimitedProducer;

final class CyclicIterator extends LimitedProducer
{
    /** @var array<string|int, mixed> */
    private array $elements;
    
    private bool $keepKeys;
    
    /**
     * @param array<string|int, mixed> $elements
     */
    public function __construct(array $elements, bool $keepKeys = false, int $limit = \PHP_INT_MAX)
    {
        parent::__construct($limit);
        
        $this->elements = $elements;
        $this->keepKeys = $keepKeys;
    }
    
    public function getIterator(): \Generator
    {
        if (empty($this->elements)) {
            yield from [];
        } elseif ($this->keepKeys) {
            $count = -1;
            
            while (true) {
                foreach ($this->elements as $key => $value) {
                    if (++$count < $this->limit) {
                        yield $key => $value;
                    } else {
                        break 2;
                    }
                }
            }
        } else {
            $index = -1;
            
            while (true) {
                foreach ($this->elements as $value) {
                    if (++$index < $this->limit) {
                        yield $index => $value;
                    } else {
                        break 2;
                    }
                }
            }
        }
    }
}