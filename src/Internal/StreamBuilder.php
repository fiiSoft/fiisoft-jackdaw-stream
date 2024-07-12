<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

interface StreamBuilder
{
    /**
     * @param iterable<mixed, mixed> $stream
     * @return iterable<mixed, mixed>
     */
    public function buildStream(iterable $stream): iterable;
}