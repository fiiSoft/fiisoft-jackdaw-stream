<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Operation\Internal;

use FiiSoft\Jackdaw\Discriminator\ByKey;
use FiiSoft\Jackdaw\Discriminator\Discriminator;
use FiiSoft\Jackdaw\Discriminator\DiscriminatorReady;
use FiiSoft\Jackdaw\Discriminator\Discriminators;
use FiiSoft\Jackdaw\Internal\Item;
use FiiSoft\Jackdaw\Internal\Signal;

abstract class GroupingOperation extends BaseOperation
{
    protected Discriminator $discriminator;
    
    protected array $collections = [];
    
    /**
     * @param DiscriminatorReady|callable|array|string|int $discriminator
     */
    final protected static function shouldReindex($discriminator, ?bool $reindex = null): bool
    {
        if ($discriminator instanceof ByKey) {
            if ($reindex === null) {
                $reindex = true;
            }
        } elseif ($reindex === null) {
            $reindex = false;
        }
        
        return $reindex;
    }
    
    /**
     * @param DiscriminatorReady|callable|array|string|int $discriminator
     */
    final protected function __construct($discriminator)
    {
        $this->discriminator = Discriminators::prepare($discriminator);
    }
    
    final public function handle(Signal $signal): void
    {
        $this->collect($signal->item);
    }
    
    abstract protected function collect(Item $item): void;
    
    final public function destroy(): void
    {
        if (!$this->isDestroying) {
            $this->collections = [];
            
            parent::destroy();
        }
    }
}