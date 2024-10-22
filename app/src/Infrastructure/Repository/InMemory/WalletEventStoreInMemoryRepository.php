<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\InMemory;

use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\WalletEventStoreRepositoryInterface;
use App\Domain\Wallet\Events\WalletCreatedEvent;
use App\Domain\Wallet\Events\WalletCreditEvent;
use App\Domain\Wallet\Events\WalletDebitEvent;
use App\Domain\Wallet\Wallet;
use App\Domain\Wallet\WalletId;

class WalletEventStoreInMemoryRepository implements WalletEventStoreRepositoryInterface
{
    private array $events = [];

    public function save(Wallet $wallet): void
    {
        $this->events[$wallet->getWalletId()->jsonSerialize()] = $wallet->getEvents();
    }

    /**
     * @throws NotFoundException
     */
    public function getById(WalletId $walletId): Wallet
    {
        if (!isset($this->events[$walletId->jsonSerialize()])) {
            throw new NotFoundException();
        }

        $events = $this->events[$walletId->jsonSerialize()];
        /** @var WalletCreatedEvent $createdEvent */
        $createdEvent = $events[0];
        $wallet = new Wallet(
            $createdEvent->walletId,
            $createdEvent->ownerId,
            $createdEvent->currency,
        );

        foreach ($events as $event) {
            switch (get_class($event)) {
                case WalletCreditEvent::class:
                    /** @var WalletCreditEvent $event */
                    $wallet->credit($event->payment);
                    break;
                case WalletDebitEvent::class:
                    /** @var WalletDebitEvent $event */
                    $wallet->debit($event->payment);
                    break;
            }
        }

        return $wallet;
    }
}
