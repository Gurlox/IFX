<?php

declare(strict_types=1);

namespace App\Application\CommandHandler;

use App\Application\Command\DebitWalletCommand;
use App\Domain\Payment\Payment;
use App\Domain\Payment\PaymentId;
use App\Domain\Repository\WalletEventStoreRepositoryInterface;
use App\Domain\Wallet\WalletId;
use Money\Currency;
use Money\Money;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class DebitWalletCommandHandler
{
    public function __construct(
        private readonly WalletEventStoreRepositoryInterface $walletEventStoreRepository
    ) {
    }

    public function __invoke(DebitWalletCommand $command): void
    {
        $walletId = WalletId::fromString($command->walletId);
        $wallet = $this->walletEventStoreRepository->getById($walletId);
        $payment = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money($command->amount, new Currency($command->currency)),
            new \DateTimeImmutable(),
        );

        $wallet->debit($payment);

        $this->walletEventStoreRepository->save($wallet);
    }
}
