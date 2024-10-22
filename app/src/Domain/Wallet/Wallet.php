<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

use App\Domain\Exception\ActionNotAllowedException;
use App\Domain\Exception\ValidationException;
use App\Domain\Payment\Payment;
use App\Domain\Wallet\Events\WalletCreatedEvent;
use App\Domain\Wallet\Events\WalletCreditEvent;
use App\Domain\Wallet\Events\WalletDebitEvent;
use App\Domain\Wallet\Events\WalletEventInterface;
use Money\Currency;
use Money\Money;

class Wallet
{
    public const MAXIMUM_DEBIT_PAYMENTS_FOR_DAY = 3;

    public const DEBIT_PAYMENT_FEE = 0.005;

    private WalletId $walletId;

    private OwnerId $ownerId;

    private Money $balance;

    private WalletEventsCollection $events;

    public function __construct(WalletId $walletId, OwnerId $ownerId, Currency $currency)
    {
        $this->events = new WalletEventsCollection();
        $this->record(new WalletCreatedEvent(
            $ownerId,
            $walletId,
            $currency,
        ));
    }

    public function getWalletId(): WalletId
    {
        return $this->walletId;
    }

    public function getOwnerId(): OwnerId
    {
        return $this->ownerId;
    }

    public function getCurrency(): Currency
    {
        return $this->balance->getCurrency();
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function getEvents(): WalletEventsCollection
    {
        return $this->events;
    }

    /**
     * @throws ValidationException
     */
    public function credit(Payment $payment): void
    {
        if (
            false === $payment->isCredit() ||
            false === $this->balance->getCurrency()->equals($payment->getCurrency())
        ) {
            throw new ValidationException('Credit for this Payment is not allowed');
        }

        $this->record(new WalletCreditEvent($payment));
    }

    /**
     * @throws ValidationException
     * @throws ActionNotAllowedException
     */
    public function debit(Payment $payment): void
    {
        if (
            false === $payment->isDebit() ||
            false === $this->balance->getCurrency()->equals($payment->getCurrency())
        ) {
            throw new ValidationException('Debit for this Payment is not allowed');
        }

        if ($this->countDebitPaymentsFromToday() >= self::MAXIMUM_DEBIT_PAYMENTS_FOR_DAY) {
            throw new ActionNotAllowedException('Maximum debit payments limit is reached for today');
        }

        $payment->addPercentageTransactionFee(self::DEBIT_PAYMENT_FEE);

        if (true === $this->balance->add($payment->getTotalAmount())->isNegative()) {
            throw new ActionNotAllowedException('Not enough balance in wallet');
        }

        $this->record(new WalletDebitEvent($payment));
    }

    private function countDebitPaymentsFromToday(): int
    {
        $count = 0;
        foreach ($this->events as $event) {
            if (
                !$event instanceof WalletDebitEvent ||
                false ===$event->payment->isPaymentFromToday()
            ) {
                continue;
            }

            $count++;
        }

        return $count;
    }

    private function onWalletCreate(WalletCreatedEvent $event): void
    {
        $this->balance = new Money(0, $event->currency);
        $this->walletId = $event->walletId;
        $this->ownerId = $event->ownerId;
    }

    private function onWalletCredit(WalletCreditEvent $event): void
    {
        $this->balance = $this->balance->add($event->payment->getAmount());
    }

    private function onWalletDebit(WalletDebitEvent $event): void
    {
        $this->balance = $this->balance
            ->add($event->payment->getAmount())
            ->add($event->payment->getPaymentFee());
    }

    private function record(WalletEventInterface $event): void
    {
        $this->events->append($event);
        $this->apply($event);
    }

    /**
     * @throws ValidationException
     */
    private function apply(WalletEventInterface $event): void
    {
        switch (get_class($event)) {
            case WalletCreatedEvent::class:
                $this->onWalletCreate($event);
                break;
            case WalletCreditEvent::class:
                $this->onWalletCredit($event);
                break;
            case WalletDebitEvent::class:
                $this->onWalletDebit($event);
                break;
            default:
                throw new ValidationException(get_class($event) . ' Event not supported for wallet');
        }
    }
}
