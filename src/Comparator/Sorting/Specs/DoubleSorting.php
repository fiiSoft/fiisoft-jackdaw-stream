<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Comparator\Sorting\Specs;

use FiiSoft\Jackdaw\Comparator\Comparator;
use FiiSoft\Jackdaw\Comparator\Sorting\Sorting;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\KeyAscValueAscComparator;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\KeyAscValueDescComparator;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\KeyDescValueAscComparator;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\KeyDescValueDescComparator;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\ValueAscKeyAscComparator;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\ValueAscKeyDescComparator;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\ValueDescKeyAscComparator;
use FiiSoft\Jackdaw\Comparator\ValueKeyCombined\ValueDescKeyDescComparator;
use FiiSoft\Jackdaw\Internal\Check;

final class DoubleSorting extends Sorting
{
    private const SINGLE_MODES = [Check::VALUE, Check::KEY];
    
    private Sorting $first;
    private Sorting $second;
    
    private ?Comparator $comparator = null;
    
    public function __construct(Sorting $first, Sorting $second)
    {
        if (!$this->isSingleMode($first)) {
            throw new \InvalidArgumentException('Invalid param first');
        }
        
        if (!$this->isSingleMode($second)) {
            throw new \InvalidArgumentException('Invalid param second');
        }
        
        if ($first->mode() === $second->mode()) {
            throw new \LogicException('Sorting specifications cannot be of the same type');
        }
        
        $this->first = $first;
        $this->second = $second;
    }
    
    private function isSingleMode(Sorting $sorting): bool
    {
        return \in_array($sorting->mode(), self::SINGLE_MODES, true);
    }
    
    public function comparator(): Comparator
    {
        if ($this->comparator === null) {
            $this->comparator = $this->createComparator();
        }
        
        return $this->comparator;
    }
    
    public function mode(): int
    {
        return Check::BOTH;
    }
    
    public function isReversed(): bool
    {
        return false;
    }
    
    public function getReversed(): Sorting
    {
        return new self($this->first->getReversed(), $this->second->getReversed());
    }
    
    private function createComparator(): Comparator
    {
        $choice = \implode('_', [
            $this->describeMode($this->first),
            $this->describeDirection($this->first),
            $this->describeMode($this->second),
            $this->describeDirection($this->second),
        ]);
        
        if ($this->first->mode() === Check::VALUE) {
            $valueComparator = $this->first->comparator();
            $keyComparator = $this->second->comparator();
        } else {
            $keyComparator = $this->first->comparator();
            $valueComparator = $this->second->comparator();
        }
        
        switch ($choice) {
            case 'value_asc_key_asc': return new ValueAscKeyAscComparator($valueComparator, $keyComparator);
            case 'value_asc_key_desc': return new ValueAscKeyDescComparator($valueComparator, $keyComparator);
            case 'value_desc_key_desc': return new ValueDescKeyDescComparator($valueComparator, $keyComparator);
            case 'value_desc_key_asc': return new ValueDescKeyAscComparator($valueComparator, $keyComparator);
            case 'key_asc_value_asc': return new KeyAscValueAscComparator($valueComparator, $keyComparator);
            case 'key_asc_value_desc': return new KeyAscValueDescComparator($valueComparator, $keyComparator);
            case 'key_desc_value_desc': return new KeyDescValueDescComparator($valueComparator, $keyComparator);
            case 'key_desc_value_asc': return new KeyDescValueAscComparator($valueComparator, $keyComparator);
            
            //@codeCoverageIgnoreStart
            default:
                throw new \UnexpectedValueException('Unknown choice in DoubleSorting::createComparator: '.$choice);
            //@codeCoverageIgnoreEnd
        }
    }
    
    private function describeMode(Sorting $sorting): string
    {
        return $sorting->mode() === Check::VALUE ? 'value' : 'key';
    }
    
    private function describeDirection(Sorting $sorting): string
    {
        return $sorting->isReversed() ? 'desc' : 'asc';
    }
}