<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Fields;

use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Exception\InvalidBytesException;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Uuid;
use ValueError;
use function base64_decode;
use function bin2hex;
use function dechex;
use function sprintf;
use function str_pad;
use function strlen;
use function substr;
use function unpack;
use const STR_PAD_LEFT;

/**
 * @author Mougrim <for-open-source@mougrim.io>
 */
class FastBytesFields implements FieldsInterface
{
    private const LAST_3_BITS_IN_4_BITS_DIGIT = 0b1110;
    private const LAST_2_BITS_IN_4_BITS_DIGIT = 0b1100;
    private const VARIANT_RESERVED_FUTURE = 0b1110;
    private const VARIANT_RESERVED_MICROSOFT = 0b1100;
    private const VARIANT_RFC_4122 = 0b1000;

    private ?int $variant = null;
    private ?int $version = null;
    private ?Hexadecimal $clockSeq = null;
    private ?Hexadecimal $timestamp = null;
    private ?Hexadecimal $clockSeqHiAndReserved = null;
    private ?Hexadecimal $clockSeqLow = null;
    private ?Hexadecimal $node = null;
    private ?Hexadecimal $timeHiAndVersion = null;
    private ?Hexadecimal $timeLow = null;
    private ?Hexadecimal $timeMid = null;

    /**
     * @param string $bytes A 16-byte binary string representation of a UUID
     */
    public function __construct(
        readonly private string $bytes,
    ) {
        if (strlen($this->bytes) !== 16) {
            throw new InvalidArgumentException(
                'The byte string must be 16 bytes long; received ' . strlen($this->bytes) . ' bytes',
            );
        }
    }

    public function getBytes(): string
    {
        return $this->bytes;
    }

    public function getClockSeq(): Hexadecimal
    {
        if ($this->clockSeq === null) {
            $this->clockSeq = $this->calculateClockSeq();
        }

        return $this->clockSeq;
    }

    private function calculateClockSeq(): Hexadecimal
    {
        if ($this->isMax()) {
            $clockSeq = 'ffff';
        } elseif ($this->isNil()) {
            $clockSeq = '0000';
        } else {
            $clockSeqPart = unpack('n', substr($this->bytes, 8, 2));
            if ($clockSeqPart === false) {
                throw new InvalidBytesException(
                    "Can't unpack clock seq part, clock seq part: "
                        . bin2hex(substr($this->bytes, 8, 2)),
                );
            }
            $clockSeq = str_pad(
                dechex($clockSeqPart[1] & 0x3fff),
                4,
                '0',
                STR_PAD_LEFT,
            );
        }

        return new Hexadecimal($clockSeq);
    }

    public function getClockSeqHiAndReserved(): Hexadecimal
    {
        if ($this->clockSeqHiAndReserved === null) {
            $this->clockSeqHiAndReserved = new Hexadecimal(bin2hex($this->bytes[8]));
        }

        return $this->clockSeqHiAndReserved;
    }

    public function getClockSeqLow(): Hexadecimal
    {
        if ($this->clockSeqLow === null) {
            $this->clockSeqLow = new Hexadecimal(bin2hex($this->bytes[9]));
        }

        return $this->clockSeqLow;
    }

    public function getNode(): Hexadecimal
    {
        if ($this->node === null) {
            $this->node = new Hexadecimal(bin2hex(substr($this->bytes, 10)));
        }

        return $this->node;
    }

    public function getTimeHiAndVersion(): Hexadecimal
    {
        if ($this->timeHiAndVersion === null) {
            $this->timeHiAndVersion = new Hexadecimal(bin2hex($this->getTimeHiAndVersionBinary()));
        }

        return $this->timeHiAndVersion;
    }

    private function getTimeHiAndVersionBinary(): string
    {
        return substr($this->bytes, 6, 2);
    }

    private function getTimeHiAndVersionDec(): int
    {
        $timeHiAndVersionPart = unpack('n', $this->getTimeHiAndVersionBinary());
        if ($timeHiAndVersionPart === false) {
            throw new InvalidBytesException(
                "Can't unpack timeHiAndVersion part, timeHiAndVersion part: "
                    . bin2hex($this->getTimeHiAndVersionBinary()),
            );}
        return $timeHiAndVersionPart[1];
    }

    public function getTimeLow(): Hexadecimal
    {
        if ($this->timeLow === null) {
            $this->timeLow = new Hexadecimal(bin2hex(substr($this->bytes, 0, 4)));
        }

        return $this->timeLow;
    }

    public function getTimeMid(): Hexadecimal
    {
        if ($this->timeMid === null) {
            $this->timeMid = new Hexadecimal(bin2hex(substr($this->bytes, 4, 2)));
        }

        return $this->timeMid;
    }

    /**
     * Returns the full 60-bit timestamp, without the version
     *
     * For version 2 UUIDs, the time_low field is the local identifier and
     * should not be returned as part of the time. For this reason, we set the
     * bottom 32 bits of the timestamp to 0's. As a result, there is some loss
     * of fidelity of the timestamp, for version 2 UUIDs. The timestamp can be
     * off by a range of 0 to 429.4967295 seconds (or 7 minutes, 9 seconds, and
     * 496730 microseconds).
     *
     * For version 6 UUIDs, the timestamp order is reversed from the typical RFC
     * 4122 order (the time bits are in the correct bit order, so that it is
     * monotonically increasing). In returning the timestamp value, we put the
     * bits in the order: time_low + time_mid + time_hi.
     */
    public function getTimestamp(): Hexadecimal
    {
        if ($this->timestamp === null) {
            $this->timestamp = $this->calculateTimestamp();
        }

        return $this->timestamp;
    }

    private function calculateTimestamp(): Hexadecimal
    {
        $timestamp = match ($this->getVersion()) {
            Uuid::UUID_TYPE_DCE_SECURITY => sprintf(
                '%03x%04s%08s',
                $this->getTimeHiAndVersionDec() & 0x0fff,
                $this->getTimeMid()->toString(),
                '',
            ),
            Uuid::UUID_TYPE_REORDERED_TIME => sprintf(
                '%08s%04s%03x',
                $this->getTimeLow()->toString(),
                $this->getTimeMid()->toString(),
                $this->getTimeHiAndVersionDec() & 0x0fff,
            ),
            // The Unix timestamp in version 7 UUIDs is a 48-bit number,
            // but for consistency, we will return a 60-bit number, padded
            // to the left with zeros.
            Uuid::UUID_TYPE_UNIX_TIME => sprintf(
                '%011s%04s',
                $this->getTimeLow()->toString(),
                $this->getTimeMid()->toString(),
            ),
            default => sprintf(
                '%03x%04s%08s',
                $this->getTimeHiAndVersionDec() & 0x0fff,
                $this->getTimeMid()->toString(),
                $this->getTimeLow()->toString(),
            ),
        };

        return new Hexadecimal($timestamp);
    }

    public function getVersion(): ?int
    {
        if ($this->version === null) {
            $this->version = $this->calculateVersion();
        }

        return $this->version;
    }

    private function calculateVersion(): ?int
    {
        if ($this->isNil() || $this->isMax()) {
            return null;
        }

        if ($this->getVariant() !== Uuid::RFC_4122) {
            return null;
        }

        $versionPart = unpack('C', $this->bytes[6]);
        if ($versionPart === false) {
            throw new InvalidBytesException(
                "Can't unpack version part, version part: " . bin2hex($this->bytes[6]),
            );
        }

        return $versionPart[1] >> 4;
    }

    /**
     * Returns true if the byte string represents a max UUID
     */
    public function isMax(): bool
    {
        return $this->getBytes() === "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
    }

    /**
     * Returns true if the byte string represents a nil UUID
     */
    public function isNil(): bool
    {
        return $this->getBytes() === "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
    }

    /**
     * Returns the variant identifier, according to RFC 4122, for the given bytes
     *
     * The following values may be returned:
     *
     * - `0` -- Reserved, NCS backward compatibility.
     * - `2` -- The variant specified in RFC 4122.
     * - `6` -- Reserved, Microsoft Corporation backward compatibility.
     * - `7` -- Reserved for future definition.
     *
     * @link https://tools.ietf.org/html/rfc4122#section-4.1.1 RFC 4122, § 4.1.1: Variant
     *
     * @return int The variant identifier, according to RFC 4122
     */
    public function getVariant(): int
    {
        if ($this->variant === null) {
            $this->variant = $this->calculateVariant();
        }

        return $this->variant;
    }

    private function calculateVariant(): int
    {
        if ($this->isMax() || $this->isNil()) {
            // RFC 4122 defines these special types of UUID, so we will consider
            // them as belonging to the RFC 4122 variant.
            return Uuid::RFC_4122;
        }

        $variantPart = unpack('C', $this->bytes[8]);
        if ($variantPart === false) {
            throw new InvalidBytesException(
                "Can't unpack variant part, variant part: " . bin2hex($this->bytes[8]),
            );
        }
        $variantPart = ($variantPart[1] >> 4) & self::LAST_3_BITS_IN_4_BITS_DIGIT;
        if ($variantPart === self::VARIANT_RESERVED_FUTURE) {
            return Uuid::RESERVED_FUTURE;
        }
        if ($variantPart === self::VARIANT_RESERVED_MICROSOFT) {
            return Uuid::RESERVED_MICROSOFT;
        }

        $variantPart &= self::LAST_2_BITS_IN_4_BITS_DIGIT;
        if ($variantPart === self::VARIANT_RFC_4122) {
            return Uuid::RFC_4122;
        }

        return Uuid::RESERVED_NCS;
    }

    /**
     * Returns a string representation of object
     */
    public function serialize(): string
    {
        return $this->getBytes();
    }

    /**
     * @return array{bytes: string}
     */
    public function __serialize(): array
    {
        return ['bytes' => $this->getBytes()];
    }

    /**
     * Constructs the object from a serialized string representation
     *
     * @param string $data The serialized string representation of the object
     *
     * @psalm-suppress UnusedMethodCall
     */
    public function unserialize(string $data): void
    {
        $this->variant = null;
        $this->version = null;
        $this->clockSeq = null;
        $this->timestamp = null;
        $this->clockSeqHiAndReserved = null;
        $this->clockSeqLow = null;
        $this->node = null;
        $this->timeHiAndVersion = null;
        $this->timeLow = null;
        $this->timeMid = null;

        if (strlen($data) === 16) {
            $this->__construct($data);
        } else {
            $this->__construct(base64_decode($data));
        }
    }

    /**
     * @param array{bytes?: string} $data
     *
     * @psalm-suppress UnusedMethodCall
     */
    public function __unserialize(array $data): void
    {
        // @codeCoverageIgnoreStart
        if (!isset($data['bytes'])) {
            throw new ValueError(sprintf('%s(): Argument #1 ($data) is invalid', __METHOD__));
        }
        // @codeCoverageIgnoreEnd

        $this->unserialize($data['bytes']);
    }
}
