<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

abstract class StreamPipe extends ForkCollaborator
{
    protected function prepareSubstream(bool $isLoop): void
    {
        throw new \BadMethodCallException('Method '.__METHOD__.' should never be called');
    }
    
    protected function continueIteration(bool $once = false): bool
    {
        throw new \BadMethodCallException('Method '.__METHOD__.' should never be called');
    }
    
    protected function finish(): void
    {
        throw new \BadMethodCallException('Method '.__METHOD__.' should never be called');
    }
}