<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Producer\Generator\Uuid\Exception;

use FiiSoft\Jackdaw\Exception\JackdawException;

/**
 * @codeCoverageIgnore
 */
final class UuidUnavailableException extends JackdawException
{
    public static function create(): self
    {
        return new self('You have to have installed either ramsey/uuid or symfony/uid to create default UuidProvider');
    }
}