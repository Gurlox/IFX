<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Events;

use App\Domain\Payment\Payment;

class WalletDebitEvent implements WalletEventInterface
{
    public function __construct(
        public readonly Payment $payment
    ) {
    }
}
