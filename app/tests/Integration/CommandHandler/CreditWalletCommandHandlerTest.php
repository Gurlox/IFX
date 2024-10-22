<?php

declare(strict_types=1);

namespace App\Tests\Integration\CommandHandler;

use App\Application\Command\CreditWalletCommand;
use App\Domain\Repository\WalletEventStoreRepositoryInterface;
use App\Domain\Wallet\OwnerId;
use App\Domain\Wallet\Wallet;
use App\Domain\Wallet\WalletId;
use Money\Currency;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

class CreditWalletCommandHandlerTest extends KernelTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        parent::bootKernel();
    }

    public function testInvokeShouldAddCreditToWallet(): void
    {
        // given
        /** @var WalletEventStoreRepositoryInterface $walletEventStoreRepository */
        $walletEventStoreRepository = $this->getContainer()->get(WalletEventStoreRepositoryInterface::class);
        /** @var MessageBusInterface $messageBus */
        $messageBus = $this->getContainer()->get(MessageBusInterface::class);

        $walletId = WalletId::fromString((string) Uuid::v4());
        $wallet = new Wallet(
            $walletId,
            OwnerId::fromString((string) Uuid::v4()),
            new Currency('USD'),
        );
        $command = new CreditWalletCommand(
            $walletId->jsonSerialize(),
            1000,
            'USD',
        );


        // when
        $walletEventStoreRepository->save($wallet);
        $messageBus->dispatch($command);

        // then
        $wallet = $walletEventStoreRepository->getById($walletId);
        self::assertEquals(1000, (int) $wallet->getBalance()->getAmount());
        self::assertCount(2, $wallet->getEvents());
    }
}
