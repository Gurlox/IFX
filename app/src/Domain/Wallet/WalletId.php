<?php

declare(strict_types=1);

namespace App\Domain\Wallet;

use App\Domain\Exception\ValidationException;
use Symfony\Component\Uid\Uuid;

class WalletId implements \JsonSerializable, \Stringable
{
    private function __construct(
        private readonly Uuid $id,
    ) {
    }

    /**
     * @throws ValidationException
     */
    public static function fromString(string $id): self
    {
        try {
            return new self(new Uuid($id));
        } catch (\InvalidArgumentException) {
            throw new ValidationException("Invalid uuid {$id}");
        }
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function jsonSerialize(): string
    {
        return (string) $this;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }
}
