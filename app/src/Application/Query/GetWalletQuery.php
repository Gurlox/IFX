<?php

declare(strict_types=1);

namespace App\Application\Query;

class GetWalletQuery
{
    public function __construct(
        public readonly string $walletId,
    ) {
    }
}
