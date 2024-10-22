<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Wallet;

use App\Domain\Exception\ValidationException;
use App\Domain\Wallet\OwnerId;
use PHPUnit\Framework\TestCase;

class WalletIdTest extends TestCase
{
    /**
     * @dataProvider invalidDataProvider
     */
    public function testFromStringWithInvalidDataShouldThrowException(string $id): void
    {
        self::expectException(ValidationException::class);
        OwnerId::fromString($id);
    }

    public static function invalidDataProvider(): \Traversable
    {
        yield ['invalid_uuid'];
        yield ['11111'];
        yield ['7c10d2d2-fc3c-40e9-be01-ab513846ca6'];
    }
}
