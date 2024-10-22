<?php

declare(strict_types=1);

namespace App\Application\QueryHandler;

use App\Application\DTO\WalletDTO;
use App\Application\Query\GetWalletQuery;
use App\Application\Repository\WalletReadRepositoryInterface;
use App\Domain\Wallet\WalletId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetWalletQueryHandler
{
    public function __construct(
        private readonly WalletReadRepositoryInterface $walletReadRepository,
    ) {
    }

    public function __invoke(GetWalletQuery $query): WalletDTO
    {
        return $this->walletReadRepository->getViewModel(WalletId::fromString($query->walletId));
    }
}
