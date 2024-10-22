<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository\InMemory;

use App\Application\DTO\WalletDTO;
use App\Application\Repository\WalletReadRepositoryInterface;
use App\Domain\Exception\NotFoundException;
use App\Domain\Repository\WalletEventStoreRepositoryInterface;
use App\Domain\Wallet\Events\WalletCreatedEvent;
use App\Domain\Wallet\Events\WalletCreditEvent;
use App\Domain\Wallet\Events\WalletDebitEvent;
use App\Domain\Wallet\Wallet;
use App\Domain\Wallet\WalletId;

class WalletInMemoryRepository implements WalletEventStoreRepositoryInterface, WalletReadRepositoryInterface
{
    private array $events = [];

    public function save(Wallet $wallet): void
    {
        $this->events[$wallet->getWalletId()->jsonSerialize()] = $wallet->getEvents()->toArray();
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

    /**
     * @throws NotFoundException
     */
    public function getViewModel(WalletId $walletId): WalletDTO
    {
        $wallet = $this->getById($walletId);

        return new WalletDTO(
            $wallet->getWalletId(),
            $wallet->getOwnerId(),
            (int) $wallet->getBalance()->getAmount(),
            $wallet->getCurrency(),
        );
    }
}
