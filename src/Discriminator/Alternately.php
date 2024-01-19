<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Exception\InvalidParamException;

final class Alternately implements Discriminator
{
    private \Iterator $iterator;
    
    private array $classifiers;
    
    public function __construct(array $classifiers)
    {
        if (empty($classifiers)) {
            throw InvalidParamException::byName('classifiers');
        }
        
        $this->classifiers = $classifiers;
        $this->init();
    }
    
    /**
     * @inheritDoc
     */
    public function classify($value, $key = null)
    {
        $classifier = $this->iterator->current();
        $this->iterator->next();
        
        return $classifier;
    }
    
    public function __clone()
    {
        $this->init();
    }
    
    private function init(): void
    {
        $this->iterator = new \InfiniteIterator(new \ArrayIterator($this->classifiers));
        $this->iterator->rewind();
    }
}