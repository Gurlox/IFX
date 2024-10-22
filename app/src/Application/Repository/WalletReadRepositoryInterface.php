<?php

declare(strict_types=1);

namespace App\Application\Repository;

use App\Application\DTO\WalletDTO;
use App\Domain\Exception\NotFoundException;
use App\Domain\Wallet\WalletId;

interface WalletReadRepositoryInterface
{
    /**
     * @throws NotFoundException
     */
    public function getViewModel(WalletId $walletId): WalletDTO;
}
