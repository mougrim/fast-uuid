<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Factory;

use DateTimeInterface;
use Mougrim\FastUuid\Fields\FastBytesFields;
use Mougrim\FastUuid\Fields\FastStringFields;
use Mougrim\FastUuid\Uuid\FastBytesUuid;
use Mougrim\FastUuid\Uuid\FastStringUuid;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Validator\ValidatorInterface;
use function hex2bin;
use function str_pad;
use const STR_PAD_LEFT;

/**
 * @author Mougrim <for-open-source@mougrim.io>
 */
readonly class FastUuidFactory implements UuidFactoryInterface
{
    public function __construct(
        private UuidFactory $uuidFactory,
        private TimeConverterInterface $timeConverter,
        private TimeConverterInterface $unixTimeConverter,
    ) {
    }

    public function fromBytes(string $bytes): UuidInterface
    {
        $fields = new FastBytesFields($bytes);

        return new FastBytesUuid(
            $fields,
            $this->uuidFactory->getCodec(),
            $this->uuidFactory->getNumberConverter(),
            $fields->getVersion() === Uuid::UUID_TYPE_UNIX_TIME ? $this->unixTimeConverter
                : $this->timeConverter,
        );
    }

    public function fromDateTime(
        DateTimeInterface $dateTime,
        ?Hexadecimal $node = null,
        ?int $clockSeq = null
    ): UuidInterface {
        $uuid = $this->uuidFactory->fromDateTime($dateTime, $node, $clockSeq);

        return $this->fromBytes($uuid->getBytes());
    }

    /**
     * @psalm-pure
     */
    public function fromHexadecimal(Hexadecimal $hex): UuidInterface
    {
        return $this->fromBytes(hex2bin($hex->__toString()));
    }

    public function fromInteger(string $integer): UuidInterface
    {
        $hex = $this->uuidFactory->getNumberConverter()->toHex($integer);
        $hex = str_pad($hex, 32, '0', STR_PAD_LEFT);

        return $this->fromBytes(hex2bin($hex));
    }

    public function fromString(string $uuid): UuidInterface
    {
        $fields = new FastStringFields($uuid);

        return new FastStringUuid(
            $fields,
            $this->uuidFactory->getNumberConverter(),
            $this->timeConverter,
            $fields->getVersion() === Uuid::UUID_TYPE_UNIX_TIME ? $this->unixTimeConverter
                : $this->timeConverter,
        );
    }

    public function getValidator(): ValidatorInterface
    {
        return $this->uuidFactory->getValidator();
    }

    public function uuid1($node = null, ?int $clockSeq = null): UuidInterface
    {
        $uuid = $this->uuidFactory->uuid1($node, $clockSeq);

        return $this->fromBytes($uuid->getBytes());
    }

    public function uuid2(
        int $localDomain,
        ?IntegerObject $localIdentifier = null,
        ?Hexadecimal $node = null,
        ?int $clockSeq = null,
    ): UuidInterface {
        $uuid = $this->uuidFactory->uuid2($localDomain, $localIdentifier, $node, $clockSeq);

        return $this->fromBytes($uuid->getBytes());
    }

    public function uuid3($ns, string $name): UuidInterface
    {
        $uuid = $this->uuidFactory->uuid3($ns, $name);

        return $this->fromBytes($uuid->getBytes());
    }

    public function uuid4(): UuidInterface
    {
        $uuid = $this->uuidFactory->uuid4();

        return $this->fromBytes($uuid->getBytes());
    }

    public function uuid5($ns, string $name): UuidInterface
    {
        $uuid = $this->uuidFactory->uuid5($ns, $name);

        return $this->fromBytes($uuid->getBytes());
    }

    public function uuid6(?Hexadecimal $node = null, ?int $clockSeq = null): UuidInterface
    {
        $uuid = $this->uuidFactory->uuid6($node, $clockSeq);

        return $this->fromBytes($uuid->getBytes());
    }


    /**
     * Returns a version 7 (Unix Epoch time) UUID
     *
     * @param DateTimeInterface|null $dateTime An optional date/time from which
     *     to create the version 7 UUID. If not provided, the UUID is generated
     *     using the current date/time.
     *
     * @return UuidInterface A FastBytesUuid instance that represents a
     *     version 7 UUID
     */
    public function uuid7(?DateTimeInterface $dateTime = null): UuidInterface
    {
        $uuid = $this->uuidFactory->uuid7($dateTime);

        return $this->fromBytes($uuid->getBytes());
    }

    /**
     * Returns a version 8 (Custom) UUID
     *
     * The bytes provided may contain any value according to your application's
     * needs. Be aware, however, that other applications may not understand the
     * semantics of the value.
     *
     * @param string $bytes A 16-byte octet string. This is an open blob
     *     of data that you may fill with 128 bits of information. Be aware,
     *     however, bits 48 through 51 will be replaced with the UUID version
     *     field, and bits 64 and 65 will be replaced with the UUID variant. You
     *     MUST NOT rely on these bits for your application needs.
     *
     * @return UuidInterface A FastBytesUuid instance that represents a
     *     version 8 UUID
     */
    public function uuid8(string $bytes): UuidInterface
    {
        $uuid = $this->uuidFactory->uuid8($bytes);

        return $this->fromBytes($uuid->getBytes());
    }
}
