<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

use FiiSoft\Jackdaw\Filter\Filter;

abstract class StringFilterMulti extends StringFilter
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
        
        $this->prepare();
    }
    
    final public function inMode(?int $mode): Filter
    {
        return $mode !== null && $mode !== $this->mode
            ? static::create($mode, $this->oryginal, $this->ignoreCase)
            : $this;
    }
    
    final public function ignoreCase(): StringFilter
    {
        $copy = parent::ignoreCase();
        
        if ($copy instanceof self) {
            $copy->prepare();
        }
        
        return $copy;
    }
    
    final public function caseSensitive(): StringFilter
    {
        $copy = parent::caseSensitive();
        
        if ($copy instanceof self) {
            $copy->prepare();
        }
        
        return $copy;
    }
    
    private function prepare(): void
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