<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Condition\Condition;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\ForkCollaborator;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Mapper\Mapper;
use FiiSoft\Jackdaw\Predicate\Predicate;
use FiiSoft\Jackdaw\Producer\Producers;
use FiiSoft\Jackdaw\Stream;

final class Fork extends StreamCollaborator
{
    private Discriminator $discriminator;
    private ForkCollaborator $prototype;
    
    /** @var Stream[] */
    private array $streams = [];
    
    /**
     * @param Discriminator|Condition|Predicate|Filter|Mapper|callable|array $discriminator
     */
    public function __construct($discriminator, ForkCollaborator $prototype)
    {
        $this->discriminator = Discriminators::getAdapter($discriminator);
        $this->prototype = $prototype;
    }
    
    public function handle(Signal $signal): void
    {
        $classifier = $this->discriminator->classify($signal->item->value, $signal->item->key);
    
        if (\is_bool($classifier)) {
            $classifier = (int) $classifier;
        } elseif (!\is_string($classifier) && !\is_int($classifier)) {
            throw new \UnexpectedValueException(
                'Unsupported value was returned from discriminator (got '.Helper::typeOfParam($classifier).')'
            );
        }
        
        if (isset($this->streams[$classifier])) {
            $stream = $this->streams[$classifier];
        } else {
            $stream = $this->prototype->cloneStream();
            $this->streams[$classifier] = $stream;
        }
        
        $stream->process($signal);
    }
    
    public function streamingFinished(Signal $signal): bool
    {
        $data = \array_map(static fn(Stream $stream) => $stream->getFinalOperation()->get(), $this->streams);
        $this->streams = [];
        
        if ($this->next instanceof DataCollector) {
            $signal->continueFrom($this->next);
            
            return $this->next->acceptSimpleData($data, $signal, false);
        }
        
        $signal->restartWith(Producers::fromArray($data), $this->next);
        
        return true;
    }
    
    public function destroy(): void
    {
        if (!$this->isDestroying) {
            $temp = $this->streams;
            $this->streams = [];
            
            foreach ($temp as $stream) {
                $stream->destroy();
            }
            
            parent::destroy();
        }
    }
}