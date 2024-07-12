<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Mougrim\FastUuid\Benchmark;

use Mougrim\FastUuid\Factory\FastUuidFactoryFactory;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Based on https://github.com/ramsey/uuid/blob/4.7.6/tests/benchmark/NonLazyUuidConversionBench.php
 */
final class NonLazyUuidConversionBench
{
    private const UUID_BYTES = [
        "\x1e\x94\x42\x33\x98\x10\x41\x38\x96\x22\x56\xe1\xf9\x0c\x56\xed",
    ];

    private UuidInterface $uuid;
    private UuidInterface $fastUuid;

    public function __construct()
    {
        $this->uuid = Uuid::getFactory()->fromBytes(self::UUID_BYTES[0]);
        $this->fastUuid = (new FastUuidFactoryFactory())->create()->fromBytes(self::UUID_BYTES[0]);
    }

    public function benchStringConversionOfUuid(): void
    {
        $this->uuid->toString();
    }

    public function benchFastStringConversionOfUuid(): void
    {
        $this->fastUuid->toString();
    }
}
