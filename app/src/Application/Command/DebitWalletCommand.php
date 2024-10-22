<?php

declare(strict_types=1);

namespace App\Application\Command;

class DebitWalletCommand
{
    public function __construct(
        public readonly string $walletId,
        public readonly int $amount,
        public readonly string $currency,
    ) {
    }
}
