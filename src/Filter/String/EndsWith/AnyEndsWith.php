<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\String\EndsWith;

use FiiSoft\Jackdaw\Filter\String\EndsWith;

final class AnyEndsWith extends EndsWith
{
    /**
     * @inheritDoc
     */
    public function isAllowed($value, $key = null): bool
    {
        $length = \mb_strlen($value);
        
        if ($this->ignoreCase) {
            if ($length >= $this->length && \mb_stripos($value, $this->value, $length - $this->length) !== false) {
                return true;
            }
            
            $length = \mb_strlen($key);
            
            return $length >= $this->length && \mb_stripos($key, $this->value, $length - $this->length) !== false;
        }
        
        if ($length >= $this->length && \mb_strpos($value, $this->value, $length - $this->length) !== false) {
            return true;
        }
        
        $length = \mb_strlen($key);
        
        return $length >= $this->length && \mb_strpos($key, $this->value, $length - $this->length) !== false;
    }
    
    protected function compareCaseInsensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $length = \mb_strlen($value);
            if ($length < $this->length || \mb_stripos($value, $this->value, $length - $this->length) === false) {
                $length = \mb_strlen($key);
                if ($length < $this->length || \mb_stripos($key, $this->value, $length - $this->length) === false) {
                    continue;
                }
            }
            
            yield $key => $value;
        }
    }
    
    protected function compareCaseSensitive(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            $length = \mb_strlen($value);
            if ($length < $this->length || \mb_strpos($value, $this->value, $length - $this->length) === false) {
                $length = \mb_strlen($key);
                if ($length < $this->length || \mb_strpos($key, $this->value, $length - $this->length) === false) {
                    continue;
                }
            }
            
            yield $key => $value;
        }
    }
}