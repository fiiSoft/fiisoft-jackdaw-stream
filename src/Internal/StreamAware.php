<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Stream;

interface StreamAware
{
    public function assignStream(Stream $stream): void;
}