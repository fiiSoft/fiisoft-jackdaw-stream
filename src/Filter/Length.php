<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter;

use FiiSoft\Jackdaw\Internal\Check;

final class Length implements Filter
{
    private int $length;
    private string $type;
    
    public function __construct(int $length, string $type)
    {
        $this->length = $length;
        $this->type = $type;
    }
    
    public function isAllowed($value, $key, int $mode = Check::VALUE): bool
    {
        switch ($mode) {
            case Check::VALUE:
                return $this->compare($this->length($value));
            case Check::KEY:
                return $this->compare($this->length($key));
            case Check::BOTH:
                return $this->compare($this->length($value)) && $this->compare($this->length($key));
            case Check::ANY:
                return $this->compare($this->length($value)) || $this->compare($this->length($key));
            default:
                throw new \InvalidArgumentException('Invalid param mode');
        }
    }
    
    private function length($value): int
    {
        if (\is_array($value)) {
            $length = \count($value);
        } elseif (\is_string($value)) {
            if (\function_exists('mb_strlen')) {
                $length = \mb_strlen($value);
            } else {
                $length = \strlen($value);
            }
        } else {
            throw new \InvalidArgumentException('Only arrays and strings are supported in Length filter');
        }
        
        return $length;
    }
    
    private function compare(int $length): bool
    {
        switch ($this->type) {
            case 'eq': return $length === $this->length;
            case 'ne': return $length !== $this->length;
            case 'gt': return $length > $this->length;
            case 'ge': return $length >= $this->length;
            case 'lt': return $length < $this->length;
            case 'le': return $length <= $this->length;
            default:
                throw new \UnexpectedValueException('Unknown type '.$this->type);
        }
    }
}