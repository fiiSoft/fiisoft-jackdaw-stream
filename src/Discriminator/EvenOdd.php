<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Internal\Check;

final class EvenOdd implements Discriminator
{
    /** @var int */
    private $mode;
    
    public function __construct(int $mode = Check::VALUE)
    {
        $this->mode = Check::getMode($mode);
    }
    
    public function classify($value, $key)
    {
        switch ($this->mode) {
            case Check::VALUE: return $this->check($value);
            case Check::KEY: return $this->check($key);
            default:
                $valueDiscr = $this->check($value);
                $keyDiscr = $this->check($key);
            
                if ($valueDiscr === $keyDiscr) {
                    return $valueDiscr;
                }
            
                return 'value_'.$valueDiscr.'_key_'.$keyDiscr;
        }
        
    }
    
    private function check($value): string
    {
        if (\is_int($value)) {
            return ($value & 1) === 0 ? 'even' : 'odd';
        }
        
        throw new \UnexpectedValueException('EvenOdd discriminator can be used only with integers!');
    }
}