<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation;

use FiiSoft\Jackdaw\Collector\Collector;
use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Consumer\Consumer;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\ResultApi;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Operation\Internal\LastOperation;
use FiiSoft\Jackdaw\Operation\Internal\StreamPipeOperation;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Reducer\Reducer;
use FiiSoft\Jackdaw\Stream;

final class Dispatch extends StreamPipeOperation
{
    private Discriminator $discriminator;
    
    /**
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|array $discriminator
     * @param array<Stream|LastOperation|ResultApi|Collector|Consumer|Reducer> $handlers
     */
    public function __construct($discriminator, array $handlers)
    {
        parent::__construct($handlers);
        
        $this->discriminator = Discriminators::getAdapter($discriminator);
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
        
        if (\is_bool($classifier)) {
            $classifier = (int) $classifier;
        } elseif (!\is_string($classifier) && !\is_int($classifier)) {
            throw new \UnexpectedValueException(
                'Value returned from discriminator is inappropriate (got '.Helper::typeOfParam($classifier).')'
            );
        }
        
        if (isset($this->handlers[$classifier])) {
            $this->handlers[$classifier]->handle($signal);
        } else {
            throw new \RuntimeException('There is no handler defined for classifier '.$classifier);
        }
        
        $this->next->handle($signal);
    }
}