<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\Filter;

abstract class StringFilterMulti extends AbstractStringFilter
{
    /** @var array<string, int|bool> */
    protected array $values = [];
    
    /** @var string[] */
    protected array $oryginal;
    
    /**
     * @param string[] $values
     */
    abstract protected static function create(int $mode, array $values, bool $ignoreCase = false): self;
    
    /**
     * @param string[] $values
     */
    final protected function __construct(int $mode, array $values, bool $ignoreCase)
    {
        parent::__construct($mode, $ignoreCase);
        
        $this->oryginal = $values;
        
        $this->initialize();
    }
    
    final public function inMode(?int $mode): StringFilter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode, $this->oryginal, $this->ignoreCase)
            : $this;
    }
    
    /**
     * @return static
     */
    final public function ignoreCase(): StringFilter
    {
        if ($this->ignoreCase) {
            return $this;
        }
        
        $copy = parent::ignoreCase();
        $copy->initialize();
        
        return $copy;
    }
    
    /**
     * @return static
     */
    final public function caseSensitive(): StringFilter
    {
        if ($this->ignoreCase) {
            $copy = parent::caseSensitive();
            $copy->initialize();
            
            return $copy;
        }
        
        return $this;
    }
    
    final public function equals(Filter $other): bool
    {
        return $other === $this || $other instanceof $this
            && $other->values === $this->values
            && $other->oryginal === $this->oryginal
            && parent::equals($other);
    }
    
    private function initialize(): void
    {
        $this->values = [];
        
        if ($this->ignoreCase) {
            foreach ($this->oryginal as $value) {
                $this->values[\mb_strtolower($value)] = true;
            }
        } else {
            $this->values = \array_flip($this->oryginal);
        }
    }
}