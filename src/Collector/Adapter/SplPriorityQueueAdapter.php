<?php declare(strict_types=1);

namespace FiiSoft\Jackdaw\Collector\Adapter;

use FiiSoft\Jackdaw\Collector\BaseCollector;

final class SplPriorityQueueAdapter extends BaseCollector
{
    private \SplPriorityQueue $queue;
    
    /** @var mixed */
    private $priority;
    
    /**
     * @param \SplPriorityQueue $queue
     * @param mixed $priority
     */
    public function __construct(\SplPriorityQueue $queue, $priority = 0, ?bool $allowKeys = true)
    {
        parent::__construct($allowKeys);
        
        $this->queue = $queue;
        $this->priority = $priority;
    }
    
    public function set($key, $value): void
    {
        $this->queue->insert($value, $key);
    }
    
    public function add($value): void
    {
        $this->queue->insert($value, $this->priority);
    }
    
    /**
     * @return mixed
     */
    public function getPriority()
    {
        return $this->priority;
    }
    
    /**
     * @param mixed $priority
     */
    public function setPriority($priority): void
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