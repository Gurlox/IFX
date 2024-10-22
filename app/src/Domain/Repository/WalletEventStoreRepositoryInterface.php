<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Exception\NotFoundException;
use App\Domain\Wallet\Wallet;
use App\Domain\Wallet\WalletId;

interface WalletEventStoreRepositoryInterface
{
    public function save(Wallet $wallet): void;

    /**
     * @throws NotFoundException
     */
    public function getById(WalletId $walletId): Wallet;
}
