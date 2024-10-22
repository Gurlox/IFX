<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Wallet;

use App\Domain\Exception\ActionNotAllowedException;
use App\Domain\Exception\ValidationException;
use App\Domain\Payment\Payment;
use App\Domain\Payment\PaymentId;
use App\Domain\Wallet\Events\WalletCreatedEvent;
use App\Domain\Wallet\Events\WalletCreditEvent;
use App\Domain\Wallet\OwnerId;
use App\Domain\Wallet\Wallet;
use App\Domain\Wallet\WalletId;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class WalletTest extends TestCase
{
    public function testCreateShouldAddCreationEvent(): void
    {
        $ownerId = OwnerId::fromString((string) Uuid::v4());
        $walletId = WalletId::fromString((string) Uuid::v4());
        $currency = new Currency('USD');
        $wallet = new Wallet(
            $walletId,
            $ownerId,
            $currency,
        );

        self::assertCount(1, $wallet->getEvents());
        self::assertInstanceOf(WalletCreatedEvent::class, $wallet->getEvents()->toArray()[0]);
        self::assertEquals(0, $wallet->getBalance()->getAmount());
        self::assertEquals($ownerId, $wallet->getOwnerId());
        self::assertEquals($walletId, $wallet->getWalletId());
        self::assertEquals($currency, $wallet->getCurrency());
    }

    /**
     * @dataProvider creditWithInvalidPaymentDataProvider
     */
    public function testCreditWithInvalidPaymentShouldThrowValidationException(Payment $payment): void
    {
        // given
        $wallet = new Wallet(
            WalletId::fromString((string) Uuid::v4()),
            OwnerId::fromString((string) Uuid::v4()),
            new Currency('USD'),
        );

        // when then
        self::expectException(ValidationException::class);
        $wallet->credit($payment);
    }

    public static function creditWithInvalidPaymentDataProvider(): \Traversable
    {
        yield 'negative amount' => [
            new Payment(
                PaymentId::fromString((string) Uuid::v4()),
                new Money(-1000, new Currency('USD')),
                new \DateTimeImmutable(),
            )
        ];
        yield 'different currency' => [
            new Payment(
                PaymentId::fromString((string) Uuid::v4()),
                new Money(1000, new Currency('PLN')),
                new \DateTimeImmutable(),
            )
        ];
    }

    public function testCreditWithValidDataShouldAppendPaymentEvent(): void
    {
        // given
        $currency = new Currency('USD');
        $wallet = new Wallet(
            WalletId::fromString((string) Uuid::v4()),
            OwnerId::fromString((string) Uuid::v4()),
            $currency,
        );
        $payment1 = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(1000, $currency),
            new \DateTimeImmutable(),
        );
        $payment2 = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(200, $currency),
            new \DateTimeImmutable(),
        );

        // when then
        $wallet->credit($payment1);
        self::assertEquals(1000, (int) $wallet->getBalance()->getAmount());
        self::assertCount(2, $wallet->getEvents());
        self::assertInstanceOf(WalletCreditEvent::class, $wallet->getEvents()->toArray()[1]);

        $wallet->credit($payment2);
        self::assertEquals(1200, (int) $wallet->getBalance()->getAmount());
        self::assertCount(3, $wallet->getEvents());
        self::assertInstanceOf(WalletCreditEvent::class, $wallet->getEvents()->toArray()[2]);
    }

    /**
     * @dataProvider debitWithInvalidPaymentDataProvider
     */
    public function testDebitWithInvalidDataShouldThrowValidationException(Payment $payment): void
    {
        // given
        $wallet = new Wallet(
            WalletId::fromString((string) Uuid::v4()),
            OwnerId::fromString((string) Uuid::v4()),
            new Currency('USD'),
        );

        // when then
        self::expectException(ValidationException::class);
        $wallet->debit($payment);
    }

    public static function debitWithInvalidPaymentDataProvider(): \Traversable
    {
        yield 'positive amount' => [
            new Payment(
                PaymentId::fromString((string) Uuid::v4()),
                new Money(1000, new Currency('USD')),
                new \DateTimeImmutable(),
            )
        ];
        yield 'different currency' => [
            new Payment(
                PaymentId::fromString((string) Uuid::v4()),
                new Money(1000, new Currency('PLN')),
                new \DateTimeImmutable(),
            )
        ];
    }

    public function testDebitWithReachedDailyLimitShouldThrowActionNotAllowedException(): void
    {
        // given
        $wallet = new Wallet(
            WalletId::fromString((string) Uuid::v4()),
            OwnerId::fromString((string) Uuid::v4()),
            new Currency('USD'),
        );
        $creditPayment = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(1000, new Currency('USD')),
            new \DateTimeImmutable(),
        );
        $debitPayment1 = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(-100, new Currency('USD')),
            new \DateTimeImmutable(),
        );
        $debitPayment2 = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(-200, new Currency('USD')),
            new \DateTimeImmutable(),
        );
        $debitPayment3 = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(-50, new Currency('USD')),
            new \DateTimeImmutable(),
        );
        $debitPayment4 = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(-100, new Currency('USD')),
            new \DateTimeImmutable(),
        );

        // when
        $wallet->credit($creditPayment);
        $wallet->debit($debitPayment1);
        $wallet->debit($debitPayment2);
        $wallet->debit($debitPayment3);

        // then
        self::expectException(ActionNotAllowedException::class);
        $wallet->debit($debitPayment4);
    }

    public function testDebitWithNotEnoughBalanceForFeeShouldThrowActionNotAllowedException(): void
    {
        // given
        $wallet = new Wallet(
            WalletId::fromString((string) Uuid::v4()),
            OwnerId::fromString((string) Uuid::v4()),
            new Currency('USD'),
        );
        $creditPayment = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(1000, new Currency('USD')),
            new \DateTimeImmutable(),
        );
        $debitPayment = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(-996, new Currency('USD')),
            new \DateTimeImmutable(),
        );

        // when
        $wallet->credit($creditPayment);

        // then
        self::expectException(ActionNotAllowedException::class);
        $wallet->debit($debitPayment);
    }

    public function testDebitShouldSubtractFromBalanceWithFee(): void
    {
        // given
        $wallet = new Wallet(
            WalletId::fromString((string) Uuid::v4()),
            OwnerId::fromString((string) Uuid::v4()),
            new Currency('USD'),
        );
        $creditPayment = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(1000, new Currency('USD')),
            new \DateTimeImmutable(),
        );
        $debitPayment = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(-800, new Currency('USD')),
            new \DateTimeImmutable(),
        );

        // when
        $wallet->credit($creditPayment);

        // then
        $wallet->debit($debitPayment);
        self::assertEquals(196, (int) $wallet->getBalance()->getAmount());
    }
}
