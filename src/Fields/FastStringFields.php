<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Fields;

use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Uuid;
use ValueError;
use function dechex;
use function hexdec;
use function preg_match;
use function sprintf;
use function str_pad;
use function str_replace;
use function substr;
use const STR_PAD_LEFT;

/**
 * @author Mougrim <for-open-source@mougrim.io>
 */
class FastStringFields implements FieldsInterface
{
    /**
     * Regular expression pattern for matching a UUID of any variant.
     */
    private const VALID_PATTERN = '\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z';

    private const LAST_3_BITS_IN_4_BITS_DIGIT = 0b1110;
    private const LAST_2_BITS_IN_4_BITS_DIGIT = 0b1100;
    private const VARIANT_RESERVED_FUTURE = 0b1110;
    private const VARIANT_RESERVED_MICROSOFT = 0b1100;
    private const VARIANT_RFC_4122 = 0b1000;

    private ?int $variant = null;
    private ?int $version = null;
    private ?string $bytes = null;
    private ?Hexadecimal $clockSeq = null;
    private ?Hexadecimal $timestamp = null;

    /**
     * @param string $value RFC 4122 string uuid presentation
     */
    public function __construct(
        private readonly string $value,
    ) {
        if (!$this->isValid()) {
            throw new InvalidUuidStringException("Invalid UUID string: {$this->value}");
        }
    }

    private function isValid(): bool
    {
        return $this->isNil() || $this->isMax() || preg_match('/' . self::VALID_PATTERN . '/Dms', $this->value);
    }

    public function getBytes(): string
    {
        if ($this->bytes === null) {
            $this->bytes = (string) hex2bin(str_replace('-', '', $this->value));
        }

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
            $clockSeq = str_pad(
                dechex(hexdec(substr($this->value, 19, 4)) & 0x3fff),
                4,
                '0',
                STR_PAD_LEFT,
            );
        }

        return new Hexadecimal($clockSeq);
    }

    public function getClockSeqHiAndReserved(): Hexadecimal
    {
        return new Hexadecimal(substr($this->value, 19, 2));
    }

    public function getClockSeqLow(): Hexadecimal
    {
        return new Hexadecimal(substr($this->value, 21, 2));
    }

    public function getNode(): Hexadecimal
    {
        return new Hexadecimal(substr($this->value, 24));
    }

    public function getTimeHiAndVersion(): Hexadecimal
    {
        return new Hexadecimal(substr($this->value, 14, 4));
    }

    public function getTimeLow(): Hexadecimal
    {
        return new Hexadecimal(substr($this->value, 0, 8));
    }

    public function getTimeMid(): Hexadecimal
    {
        return new Hexadecimal(substr($this->value, 9, 4));
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
                hexdec($this->getTimeHiAndVersion()->toString()) & 0x0fff,
                $this->getTimeMid()->toString(),
                '',
            ),
            Uuid::UUID_TYPE_REORDERED_TIME => sprintf(
                '%08s%04s%03x',
                $this->getTimeLow()->toString(),
                $this->getTimeMid()->toString(),
                hexdec($this->getTimeHiAndVersion()->toString()) & 0x0fff,
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
                hexdec($this->getTimeHiAndVersion()->toString()) & 0x0fff,
                $this->getTimeMid()->toString(),
                $this->getTimeLow()->toString()
            ),
        };

        return new Hexadecimal($timestamp);
    }

    public function isNil(): bool
    {
        return $this->value === Uuid::NIL;
    }

    public function isMax(): bool
    {
        return $this->value === Uuid::MAX;
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

        $variantPart = hexdec($this->value[19]) & self::LAST_3_BITS_IN_4_BITS_DIGIT;
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

        return hexdec($this->value[14]);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function serialize(): string
    {
        return $this->value;
    }

    /**
     * @return array{value: string}
     */
    public function __serialize(): array
    {
        return ['value' => $this->value];
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
        $this->bytes = null;
        $this->clockSeq = null;
        $this->timestamp = null;

        $this->__construct($data);
    }

    /**
     * @param array{value?: string} $data
     *
     * @psalm-suppress UnusedMethodCall
     */
    public function __unserialize(array $data): void
    {
        if (!isset($data['value'])) {
            throw new ValueError(sprintf('%s(): Argument #1 ($data) is invalid', __METHOD__));
        }

        $this->unserialize($data['value']);
    }
}
