<?php

declare(strict_types=1);

namespace App\Application\DTO;

use App\Domain\Wallet\OwnerId;
use App\Domain\Wallet\WalletId;
use Money\Currency;

class WalletDTO
{
    public function __construct(
        public readonly WalletId $walletId,
        public readonly OwnerId $ownerId,
        public readonly int $balance,
        public readonly Currency $currency,
    ) {
    }
}
