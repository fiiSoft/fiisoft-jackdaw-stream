<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Filter\Logic\OpOR;

use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Internal\Check;

final class FilterORBoth extends BaseOR
{
    /**
     * @param array<FilterReady|callable|array<string|int, mixed>|scalar> $filters
     */
    protected function __construct(array $filters)
    {
        parent::__construct($filters, Check::VALUE);
        
        $this->mode = Check::BOTH;
    }
    
    public function isAllowed($value, $key = null): bool
    {
        foreach ($this->filters as $f1) {
            if ($f1->isAllowed($value)) {
                foreach ($this->filters as $f2) {
                    if ($f2->isAllowed($key)) {
                        return true;
                    }
                }
                break;
            }
        }
        
        return false;
    }
    
    public function buildStream(iterable $stream): iterable
    {
        foreach ($stream as $key => $value) {
            foreach ($this->filters as $f1) {
                if ($f1->isAllowed($value)) {
                    foreach ($this->filters as $f2) {
                        if ($f2->isAllowed($key)) {
                            yield $key => $value;
                            continue 3;
                        }
                    }
                    continue 2;
                }
            }
        }
    }
}