<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Mapping\AccumulateSeparate;

use FiiSoft\Jackdaw\Filter\FilterReady;
use FiiSoft\Jackdaw\Filter\Filters;
use FiiSoft\Jackdaw\Operation\Mapping\AccumulateSeparate;

abstract class Separate extends AccumulateSeparate
{
    /**
     * @param FilterReady|callable|mixed $filter
     */
    final public static function create($filter, ?int $mode = null, bool $reindex = false): self
    {
        $filter = Filters::getAdapter($filter, $mode);
        
        return $reindex
            ? new SeparateReindexKeys($filter, $reindex)
            : new SeparateKeepKeys($filter, $reindex);
    }
}