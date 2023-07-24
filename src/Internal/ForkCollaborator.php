<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Internal;

use FiiSoft\Jackdaw\Operation\Internal\FinalOperation;
use FiiSoft\Jackdaw\Stream;

abstract class ForkCollaborator extends ProtectedCloning
{
    protected function getFinalOperation(): FinalOperation
    {
        throw new \BadMethodCallException('Method '.__METHOD__.' should never be called');
    }
    
    protected function process(Signal $signal): bool
    {
        throw new \BadMethodCallException('Method '.__METHOD__.' should never be called');
    }
    
    protected function cloneStream(): Stream
    {
        throw new \BadMethodCallException('Method '.__METHOD__.' should never be called');
    }
}