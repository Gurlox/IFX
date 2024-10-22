<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

use App\Domain\Wallet\Events\WalletEventInterface;

/**
 * @implements \IteratorAggregate<WalletEventInterface>
 */
class WalletEventsCollection implements \Countable, \IteratorAggregate
{
    private \ArrayIterator $events;

    public function __construct(WalletEventInterface ...$event)
    {
        $this->events = new \ArrayIterator($event);
    }

    /**
     * @return WalletEventInterface[]
     */
    public function toArray(): array
    {
        return iterator_to_array($this->events, false);
    }

    public function getIterator(): \Traversable
    {
        return $this->events;
    }

    public function count(): int
    {
        return count($this->events);
    }

    public function append(WalletEventInterface $event): self
    {
        $this->events->append($event);

        return $this;
    }
}
