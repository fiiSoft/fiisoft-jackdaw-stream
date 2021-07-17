<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Terminating;

use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Filter\Filter;
use FiiSoft\Jackdaw\Internal\Helper;
use FiiSoft\Jackdaw\Internal\Signal;
use FiiSoft\Jackdaw\Internal\StreamCollection;
use FiiSoft\Jackdaw\Operation\Internal\BaseOperation;

final class GroupBy extends BaseOperation
{
    private Discriminator $discriminator;
    private bool $preserveKeys;
    
    private array $collections = [];
    
    /**
     * @param Discriminator|Filter|string|callable $discriminator
     * @param bool $preserveKeys
     */
    public function __construct($discriminator, bool $preserveKeys = false)
    {
        $this->discriminator = Discriminators::getAdapter($discriminator);
        $this->preserveKeys = $preserveKeys;
    }
    
    public function handle(Signal $signal): void
    {
        $item = $signal->item;
        
        $classifier = $this->discriminator->classify($item->value, $item->key);
    
        if (\is_bool($classifier)) {
            $classifier = (int) $classifier;
            $isOk = true;
        } else {
            $isOk = \is_string($classifier) || \is_int($classifier);
        }
        
        if ($isOk) {
            if ($this->preserveKeys) {
                $this->collections[$classifier][$item->key] = $item->value;
            } else {
                $this->collections[$classifier][] = $item->value;
            }
        } else {
            throw new \UnexpectedValueException(
                'ResultItem returned from discriminator is inappropriate (got '.Helper::typeOfParam($classifier).')'
            );
        }
    }
 
    public function result(): StreamCollection
    {
        return new StreamCollection($this->collections);
    }
}