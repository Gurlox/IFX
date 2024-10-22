<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Wallet;

use App\Domain\Wallet\Events\WalletEventInterface;
use App\Domain\Wallet\WalletEventsCollection;
use PHPUnit\Framework\TestCase;

class WalletEventsCollectionTest extends TestCase
{
    public function testCountShouldReturnValidInteger(): void
    {
        // given
        $collection = new WalletEventsCollection(...[
            $this->createMock(WalletEventInterface::class),
            $this->createMock(WalletEventInterface::class),
            $this->createMock(WalletEventInterface::class),
            $this->createMock(WalletEventInterface::class),
            $this->createMock(WalletEventInterface::class),
        ]);

        // when then
        self::assertCount(5, $collection);
    }

    public function testIterateShouldIterateThroughElements(): void
    {
        // given
        $collection = new WalletEventsCollection(...[
            $this->createMock(WalletEventInterface::class),
            $this->createMock(WalletEventInterface::class),
            $this->createMock(WalletEventInterface::class),
            $this->createMock(WalletEventInterface::class),
        ]);
        $i = 0;

        // when
        foreach ($collection as $item) {
            self::assertInstanceOf(WalletEventInterface::class, $item);
            $i++;
        }

        // then
        self::assertEquals(4, $i);
    }

    public function testAppendShouldAddEvent(): void
    {
        // given
        $collection = new WalletEventsCollection(...[
            $this->createMock(WalletEventInterface::class),
        ]);

        // when
        $collection->append($this->createMock(WalletEventInterface::class));

        // then
        self::assertEquals(2, $collection->count());
    }
}
