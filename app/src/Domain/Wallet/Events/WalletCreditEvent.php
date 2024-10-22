<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Events;

use App\Domain\Payment\Payment;

class WalletCreditEvent implements WalletEventInterface
{
    public function __construct(
        public readonly Payment $payment
    ) {
    }
}
