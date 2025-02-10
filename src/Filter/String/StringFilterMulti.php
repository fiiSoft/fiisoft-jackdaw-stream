<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String;

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
        
        $this->prepare();
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
        $copy = parent::ignoreCase();
        $copy->prepare();
        
        return $copy;
    }
    
    /**
     * @return static
     */
    final public function caseSensitive(): StringFilter
    {
        $copy = parent::caseSensitive();
        $copy->prepare();
        
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