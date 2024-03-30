<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter;

use FiiSoft\Jackdaw\Collector\BaseCollector;

final class SplPriorityQueueAdapter extends BaseCollector
{
    private \SplPriorityQueue $queue;
    
    private int $priority = 0;
    
    /**
     * @param \SplPriorityQueue $queue
     */
    public function __construct(\SplPriorityQueue $queue, ?bool $allowKeys = true)
    {
        parent::__construct($allowKeys);
        
        $this->queue = $queue;
    }
    
    public function set($key, $value): void
    {
        $this->queue->insert($value, $key);
    }
    
    public function add($value): void
    {
        $this->queue->insert($value, $this->priority);
    }
    
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }
    
    public function increasePriority(int $step = 1): void
    {
        $this->priority += $step;
    }
    
    public function decreasePriority(int $step = 1): void
    {
        $this->priority -= $step;
    }
}