<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Payment;

use App\Domain\Exception\ValidationException;
use App\Domain\Payment\PaymentId;
use PHPUnit\Framework\TestCase;

class PaymentIdTest extends TestCase
{
    /**
     * @dataProvider invalidDataProvider
     */
    public function testFromStringWithInvalidDataShouldThrowException(string $id): void
    {
        self::expectException(ValidationException::class);
        PaymentId::fromString($id);
    }

    public static function invalidDataProvider(): \Traversable
    {
        yield ['invalid_uuid'];
        yield ['11111'];
        yield ['7c10d2d2-fc3c-40e9-be01-ab513846ca6'];
    }
}
