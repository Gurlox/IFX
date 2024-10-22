<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Events;

use App\Domain\Wallet\OwnerId;
use App\Domain\Wallet\WalletId;
use Money\Currency;

class WalletCreatedEvent implements WalletEventInterface
{
    public function __construct(
        public readonly OwnerId $ownerId,
        public readonly WalletId $walletId,
        public readonly Currency $currency,
    ) {
    }
}
