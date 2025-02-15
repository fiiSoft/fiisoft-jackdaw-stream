<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

interface Executable
{
    public function run(bool $onlyIfNotRunYet = false): void;
}