<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpAND;

use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Check;

final class FilterANDAny extends BaseAND
{
    /**
     * @param array<Filter|callable|mixed> $filters
     */
    protected function __construct(array $filters)
    {
        parent::__construct($filters, Check::VALUE);
        
        $this->mode = Check::ANY;
    }
    
    public function isAllowed($value, $key = null): bool
    {
        foreach ($this->filters as $f1) {
            if ($f1->isAllowed($value)) {
                continue;
            }
            
            foreach ($this->filters as $f2) {
                if ($f2->isAllowed($key)) {
                    continue;
                }
                
                return false;
            }
            
            return true;
        }
        
        return true;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->filters as $f1) {
                if ($f1->isAllowed($value)) {
                    continue;
                }
                
                foreach ($this->filters as $f2) {
                    if ($f2->isAllowed($key)) {
                        continue;
                    }
                    
                    continue 3;
                }
                
                yield $key => $value;
                
                continue 2;
            }
            
            yield $key => $value;
        }
    }
}