<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Stream;

interface SourceAware
{
    public function assignSource(Stream $stream): void;
}