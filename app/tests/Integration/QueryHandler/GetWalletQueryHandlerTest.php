<?php

declare(strict_types=1);

namespace App\Tests\Integration\CommandHandler;

use App\Application\Command\CreditWalletCommand;
use App\Application\DTO\WalletDTO;
use App\Application\Query\GetWalletQuery;
use App\Domain\Repository\WalletEventStoreRepositoryInterface;
use App\Domain\Wallet\OwnerId;
use App\Domain\Wallet\Wallet;
use App\Domain\Wallet\WalletId;
use Money\Currency;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Uid\Uuid;

class GetWalletQueryHandlerTest extends KernelTestCase
{

    public function setUp(): void
    {
        parent::setUp();
        parent::bootKernel();
    }

    public function testInvokeShouldReturnWalletData(): void
    {
        // given
        /** @var WalletEventStoreRepositoryInterface $walletEventStoreRepository */
        $walletEventStoreRepository = $this->getContainer()->get(WalletEventStoreRepositoryInterface::class);
        /** @var MessageBusInterface $messageBus */
        $messageBus = $this->getContainer()->get(MessageBusInterface::class);

        $currency = 'USD';
        $balance = 1000;
        $ownerId = (string) Uuid::v4();
        $walletId = WalletId::fromString((string) Uuid::v4());
        $wallet = new Wallet(
            $walletId,
            OwnerId::fromString($ownerId),
            new Currency($currency),
        );
        $creditWalletCommand = new CreditWalletCommand(
            $walletId->jsonSerialize(),
            $balance,
            $currency,
        );
        $query = new GetWalletQuery(
            $walletId->jsonSerialize(),
        );

        // when
        $walletEventStoreRepository->save($wallet);
        $messageBus->dispatch($creditWalletCommand);

        // then
        /** @var WalletDTO $walletDTO */
        $walletDTO = $messageBus->dispatch($query)->last(HandledStamp::class)->getResult();

        self::assertEquals($walletId, $walletDTO->walletId);
        self::assertEquals($balance, $walletDTO->balance);
        self::assertEquals($currency, $walletDTO->currency);
        self::assertEquals($ownerId, $walletDTO->ownerId);
    }
}
