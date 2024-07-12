<?php
/** @noinspection PhpUnused */
/** @noinspection PhpExpressionResultUnusedInspection */

declare(strict_types=1);

namespace Mougrim\FastUuid\Benchmark;

use Mougrim\FastUuid\Factory\FastUuidFactoryFactory;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Based on https://github.com/ramsey/uuid/blob/4.7.6/tests/benchmark/UuidFieldExtractionBench.php
 */
final class UuidFieldExtractionBench
{
    private UuidInterface $uuid;
    private UuidInterface $lazyUuid;
    private UuidInterface $fastStringUuid;
    private UuidInterface $fastBytesUuid;
    private UuidInterface $fastLazyStringUuid;

    public function __construct()
    {
        $this->uuid = Uuid::getFactory()->fromString('0ae0cac5-2a40-465c-99ed-3d331b7cf72a');
        $this->lazyUuid = Uuid::fromString('0ae0cac5-2a40-465c-99ed-3d331b7cf72a');
        $fastUuidFactory = (new FastUuidFactoryFactory())->create();
        $this->fastStringUuid = $fastUuidFactory->fromString('0ae0cac5-2a40-465c-99ed-3d331b7cf72a');
        $this->fastBytesUuid = $fastUuidFactory->fromBytes(
            $fastUuidFactory->fromString('0ae0cac5-2a40-465c-99ed-3d331b7cf72a')->getBytes(),
        );
        $this->fastLazyStringUuid = (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true)
            ->fromString('0ae0cac5-2a40-465c-99ed-3d331b7cf72a');
    }

    public function benchGetFields(): void
    {
        $this->uuid->getFields();
    }

    public function benchLazyGetFields(): void
    {
        $this->lazyUuid->getFields();
    }

    public function benchFastStringGetFields(): void
    {
        $this->fastStringUuid->getFields();
    }

    public function benchFastBytesGetFields(): void
    {
        $this->fastBytesUuid->getFields();
    }

    public function benchFastLazyGetFields(): void
    {
        $this->fastLazyStringUuid->getFields();
    }

    public function benchGetFields10Times(): void
    {
        $this->uuid->getFields();
        $this->uuid->getFields();
        $this->uuid->getFields();
        $this->uuid->getFields();
        $this->uuid->getFields();
        $this->uuid->getFields();
        $this->uuid->getFields();
        $this->uuid->getFields();
        $this->uuid->getFields();
        $this->uuid->getFields();
    }

    public function benchLazyGetFields10Times(): void
    {
        $this->lazyUuid->getFields();
        $this->lazyUuid->getFields();
        $this->lazyUuid->getFields();
        $this->lazyUuid->getFields();
        $this->lazyUuid->getFields();
        $this->lazyUuid->getFields();
        $this->lazyUuid->getFields();
        $this->lazyUuid->getFields();
        $this->lazyUuid->getFields();
        $this->lazyUuid->getFields();
    }

    public function benchFastStringGetFields10Times(): void
    {
        $this->fastStringUuid->getFields();
        $this->fastStringUuid->getFields();
        $this->fastStringUuid->getFields();
        $this->fastStringUuid->getFields();
        $this->fastStringUuid->getFields();
        $this->fastStringUuid->getFields();
        $this->fastStringUuid->getFields();
        $this->fastStringUuid->getFields();
        $this->fastStringUuid->getFields();
        $this->fastStringUuid->getFields();
    }

    public function benchFastBytesGetFields10Times(): void
    {
        $this->fastBytesUuid->getFields();
        $this->fastBytesUuid->getFields();
        $this->fastBytesUuid->getFields();
        $this->fastBytesUuid->getFields();
        $this->fastBytesUuid->getFields();
        $this->fastBytesUuid->getFields();
        $this->fastBytesUuid->getFields();
        $this->fastBytesUuid->getFields();
        $this->fastBytesUuid->getFields();
        $this->fastBytesUuid->getFields();
    }

    public function benchFastLazyStringGetFields10Times(): void
    {
        $this->fastLazyStringUuid->getFields();
        $this->fastLazyStringUuid->getFields();
        $this->fastLazyStringUuid->getFields();
        $this->fastLazyStringUuid->getFields();
        $this->fastLazyStringUuid->getFields();
        $this->fastLazyStringUuid->getFields();
        $this->fastLazyStringUuid->getFields();
        $this->fastLazyStringUuid->getFields();
        $this->fastLazyStringUuid->getFields();
        $this->fastLazyStringUuid->getFields();
    }

    public function benchGetHex(): void
    {
        $this->uuid->getHex();
    }

    public function benchLazyGetHex(): void
    {
        $this->lazyUuid->getHex();
    }

    public function benchFastStringGetHex(): void
    {
        $this->fastStringUuid->getHex();
    }

    public function benchFastBytesGetHex(): void
    {
        $this->fastBytesUuid->getHex();
    }

    public function benchFastLazyStringGetHex(): void
    {
        $this->fastLazyStringUuid->getHex();
    }

    public function benchGetHex10Times(): void
    {
        $this->uuid->getHex();
        $this->uuid->getHex();
        $this->uuid->getHex();
        $this->uuid->getHex();
        $this->uuid->getHex();
        $this->uuid->getHex();
        $this->uuid->getHex();
        $this->uuid->getHex();
        $this->uuid->getHex();
        $this->uuid->getHex();
    }

    public function benchLazyGetHex10Times(): void
    {
        $this->lazyUuid->getHex();
        $this->lazyUuid->getHex();
        $this->lazyUuid->getHex();
        $this->lazyUuid->getHex();
        $this->lazyUuid->getHex();
        $this->lazyUuid->getHex();
        $this->lazyUuid->getHex();
        $this->lazyUuid->getHex();
        $this->lazyUuid->getHex();
        $this->lazyUuid->getHex();
    }

    public function benchFastStringGetHex10Times(): void
    {
        $this->fastStringUuid->getHex();
        $this->fastStringUuid->getHex();
        $this->fastStringUuid->getHex();
        $this->fastStringUuid->getHex();
        $this->fastStringUuid->getHex();
        $this->fastStringUuid->getHex();
        $this->fastStringUuid->getHex();
        $this->fastStringUuid->getHex();
        $this->fastStringUuid->getHex();
        $this->fastStringUuid->getHex();
    }

    public function benchFastBytesGetHex10Times(): void
    {
        $this->fastBytesUuid->getHex();
        $this->fastBytesUuid->getHex();
        $this->fastBytesUuid->getHex();
        $this->fastBytesUuid->getHex();
        $this->fastBytesUuid->getHex();
        $this->fastBytesUuid->getHex();
        $this->fastBytesUuid->getHex();
        $this->fastBytesUuid->getHex();
        $this->fastBytesUuid->getHex();
        $this->fastBytesUuid->getHex();
    }

    public function benchFastLazyStringGetHex10Times(): void
    {
        $this->fastLazyStringUuid->getHex();
        $this->fastLazyStringUuid->getHex();
        $this->fastLazyStringUuid->getHex();
        $this->fastLazyStringUuid->getHex();
        $this->fastLazyStringUuid->getHex();
        $this->fastLazyStringUuid->getHex();
        $this->fastLazyStringUuid->getHex();
        $this->fastLazyStringUuid->getHex();
        $this->fastLazyStringUuid->getHex();
        $this->fastLazyStringUuid->getHex();
    }

    public function benchGetInteger(): void
    {
        $this->uuid->getInteger();
    }

    public function benchLazyGetInteger(): void
    {
        $this->lazyUuid->getInteger();
    }

    public function benchFastStringGetInteger(): void
    {
        $this->fastStringUuid->getInteger();
    }

    public function benchFastBytesGetInteger(): void
    {
        $this->fastBytesUuid->getInteger();
    }

    public function benchFastLazyStringGetInteger(): void
    {
        $this->fastLazyStringUuid->getInteger();
    }

    public function benchGetInteger10Times(): void
    {
        $this->uuid->getInteger();
        $this->uuid->getInteger();
        $this->uuid->getInteger();
        $this->uuid->getInteger();
        $this->uuid->getInteger();
        $this->uuid->getInteger();
        $this->uuid->getInteger();
        $this->uuid->getInteger();
        $this->uuid->getInteger();
        $this->uuid->getInteger();
    }

    public function benchLazyGetInteger10Times(): void
    {
        $this->lazyUuid->getInteger();
        $this->lazyUuid->getInteger();
        $this->lazyUuid->getInteger();
        $this->lazyUuid->getInteger();
        $this->lazyUuid->getInteger();
        $this->lazyUuid->getInteger();
        $this->lazyUuid->getInteger();
        $this->lazyUuid->getInteger();
        $this->lazyUuid->getInteger();
        $this->lazyUuid->getInteger();
    }

    public function benchFastStringGetInteger10Times(): void
    {
        $this->fastStringUuid->getInteger();
        $this->fastStringUuid->getInteger();
        $this->fastStringUuid->getInteger();
        $this->fastStringUuid->getInteger();
        $this->fastStringUuid->getInteger();
        $this->fastStringUuid->getInteger();
        $this->fastStringUuid->getInteger();
        $this->fastStringUuid->getInteger();
        $this->fastStringUuid->getInteger();
        $this->fastStringUuid->getInteger();
    }

    public function benchFastBytesGetInteger10Times(): void
    {
        $this->fastBytesUuid->getInteger();
        $this->fastBytesUuid->getInteger();
        $this->fastBytesUuid->getInteger();
        $this->fastBytesUuid->getInteger();
        $this->fastBytesUuid->getInteger();
        $this->fastBytesUuid->getInteger();
        $this->fastBytesUuid->getInteger();
        $this->fastBytesUuid->getInteger();
        $this->fastBytesUuid->getInteger();
        $this->fastBytesUuid->getInteger();
    }

    public function benchFastLazyStringGetInteger10Times(): void
    {
        $this->fastLazyStringUuid->getInteger();
        $this->fastLazyStringUuid->getInteger();
        $this->fastLazyStringUuid->getInteger();
        $this->fastLazyStringUuid->getInteger();
        $this->fastLazyStringUuid->getInteger();
        $this->fastLazyStringUuid->getInteger();
        $this->fastLazyStringUuid->getInteger();
        $this->fastLazyStringUuid->getInteger();
        $this->fastLazyStringUuid->getInteger();
        $this->fastLazyStringUuid->getInteger();
    }
}
