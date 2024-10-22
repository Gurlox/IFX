<?php

declare(strict_types=1);

namespace App\Tests\Unit\Payment;

use App\Domain\Exception\ValidationException;
use App\Domain\Payment\Payment;
use App\Domain\Payment\PaymentId;
use Money\Currency;
use Money\Money;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

class PaymentTest extends TestCase
{
    public function testCreateWith0AmountShouldThrowValidationException(): void
    {
        self::expectException(ValidationException::class);
        new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(0, new Currency('USD')),
            new \DateTimeImmutable(),
        );
    }

    /**
     * @dataProvider isDebitDataProvider
     */
    public function testIsDebit(int $value, bool $isDebit): void
    {
        self::assertEquals(
            $isDebit,
            (new Payment(
                PaymentId::fromString((string) Uuid::v4()),
                new Money($value, new Currency('USD')),
                new \DateTimeImmutable(),
            ))->isDebit()
        );
    }

    public static function isDebitDataProvider(): \Traversable
    {
        yield 'negative amount, is debit' => [-100, true];
        yield 'positive amount, is not debit' => [100, false];
    }

    /**
     * @dataProvider isCreditDataProvider
     */
    public function testIsCredit(int $value, bool $isCredit): void
    {
        self::assertEquals(
            $isCredit,
            (new Payment(
                PaymentId::fromString((string) Uuid::v4()),
                new Money($value, new Currency('USD')),
                new \DateTimeImmutable(),
            ))->isCredit()
        );
    }

    public static function isCreditDataProvider(): \Traversable
    {
        yield 'negative amount, is not credit' => [-100, false];
        yield 'positive amount, is credit' => [100, true];
    }

    /**
     * @dataProvider isPaymentFromTodayDataProvider
     */
    public function testIsPaymentFromToday(\DateTimeImmutable $date, bool $result): void
    {
        // given
        $payment = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(10, new Currency('USD')),
            new \DateTimeImmutable(),
        );

        // when then
        self::assertEquals(
            (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            $payment->getDate()->format('Y-m-d H:i:s')
        );
    }

    public static function isPaymentFromTodayDataProvider(): \Traversable
    {
        yield 'tomorrow' => [new \DateTimeImmutable('+1 day'), false];
        yield '+2 days' => [new \DateTimeImmutable('+2 days'), false];
        yield 'yesterday' => [new \DateTimeImmutable('-1 day'), false];
        yield 'today' => [new \DateTimeImmutable(), false];
    }

    public function testAddPercentageTransactionFeeWithInvalidValueShouldThrowException(): void
    {
        self::expectException(ValidationException::class);
        (new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(10, new Currency('USD')),
            new \DateTimeImmutable(),
        ))->addPercentageTransactionFee(12);
    }

    /**
     * @dataProvider addPercentageTransactionFeeDataProvider
     */
    public function testAddPercentageTransactionFeeShouldReturnCorrectData(int $currentAmount, float $feePercentage, int $result): void
    {
        // given
        $payment = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money($currentAmount, new Currency('USD')),
            new \DateTimeImmutable(),
        );

        // when
        $payment->addPercentageTransactionFee($feePercentage);

        // then
        self::assertEquals($result, (int) $payment->getPaymentFee()->getAmount());
    }

    public static function addPercentageTransactionFeeDataProvider(): \Traversable
    {
        yield [1000, 0.005, 5];
        yield [100, 0.005, 1];
        yield [1200, 0.1, 120];
        yield [10, 0.01, 0];
    }

    public function testGetTotalAmountShouldGetAmountWithPaymentFee(): void
    {
        // given
        $payment = new Payment(
            PaymentId::fromString((string) Uuid::v4()),
            new Money(-1000, new Currency('USD')),
            new \DateTimeImmutable(),
        );

        // when
        $payment->addPercentageTransactionFee(0.005);

        // then
        self::assertEquals(-1005, (int) $payment->getTotalAmount()->getAmount());
    }
}
