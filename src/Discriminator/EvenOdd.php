<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Discriminator;

use FiiSoft\Jackdaw\Discriminator\EvenOdd\BothEvenOdd;
use FiiSoft\Jackdaw\Discriminator\EvenOdd\KeyEvenOdd;
use FiiSoft\Jackdaw\Discriminator\EvenOdd\ValueEvenOdd;
use FiiSoft\Jackdaw\Internal\Check;
use FiiSoft\Jackdaw\Internal\Mode;

abstract class EvenOdd implements Discriminator
{
    final public static function create(int $mode): self
    {
        switch (Mode::get($mode)) {
            case Check::VALUE:
                return new ValueEvenOdd();
            case Check::KEY:
                return new KeyEvenOdd();
            default:
                return new BothEvenOdd();
        }
    }
}