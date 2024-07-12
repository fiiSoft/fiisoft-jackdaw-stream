<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter;

use FiiSoft\Jackdaw\Collector\BaseCollector;

final class SplPriorityQueueAdapter extends BaseCollector
{
    /** @var \SplPriorityQueue<int, mixed> */
    private \SplPriorityQueue $queue;
    
    private int $priority = 0;
    
    /**
     * @param \SplPriorityQueue<int, mixed> $queue
     */
    public function __construct(\SplPriorityQueue $queue, ?bool $allowKeys = true)
    {
        parent::__construct($allowKeys);
        
        $this->queue = $queue;
    }
    
    /**
     * @param int $key
     * @param mixed $value
     */
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