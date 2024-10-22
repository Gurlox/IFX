<?php

declare(strict_types=1);

namespace App\Domain\Payment;

use App\Domain\Exception\ValidationException;
use Money\Currency;
use Money\Money;

class Payment
{
    private Money $paymentFee;

    public function __construct(
        private PaymentId $paymentId,
        private Money $amount,
        private \DateTimeImmutable $date
    ) {
        if (0 == $this->amount->getAmount()) {
            throw new ValidationException('Amount cannot be 0');
        }
    }

    public function getPaymentId(): PaymentId
    {
        return $this->paymentId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function isDebit(): bool
    {
        return $this->amount->isNegative();
    }

    public function isCredit(): bool
    {
        return $this->amount->isPositive();
    }

    public function getCurrency(): Currency
    {
        return $this->amount->getCurrency();
    }

    public function getPaymentFee(): Money
    {
        return $this->paymentFee;
    }

    public function isPaymentFromToday(): bool
    {
        return (new \DateTimeImmutable())->format('Y-m-d') === $this->date->format('Y-m-d');
    }

    public function addPercentageTransactionFee(float $percentage): self
    {
        if ($percentage < 0 || $percentage > 1) {
            throw new ValidationException('Invalid percentage amount');
        }

        $this->paymentFee = $this->amount->multiply($percentage);

        return $this;
    }

    public function getTotalAmount(): Money
    {
        return $this->amount->add($this->getPaymentFee());
    }
}
