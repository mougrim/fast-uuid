<?php
/** @noinspection PhpDeprecationInspection */
declare(strict_types=1);

namespace Mougrim\FastUuid\Uuid;

use DateTimeInterface;
use Mougrim\FastUuid\Factory\FastUuidFactoryFactory;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Fields\FieldsInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;
use Ramsey\Uuid\UuidInterface;
use ValueError;
use function hex2bin;
use function sprintf;
use function str_replace;

/**
 * Based on https://github.com/ramsey/uuid/blob/4.7.6/src/Lazy/LazyUuidFromString.php
 */
final class FastLazyUuidFromString implements UuidInterface
{
    private ?UuidInterface $unwrapped = null;

    /**
     * @psalm-param non-empty-string $uuid
     */
    public function __construct(
        private string $uuid,
        private ?string $bytes = null,
    ) {
    }

    public function serialize(): string
    {
        return $this->uuid;
    }

    /**
     * @return array{string: string}
     *
     * @psalm-return array{string: non-empty-string}
     */
    public function __serialize(): array
    {
        return ['string' => $this->uuid];
    }

    /**
     * {@inheritDoc}
     *
     * @param string $data
     *
     * @psalm-param non-empty-string $data
     */
    public function unserialize(string $data): void
    {
        $this->uuid = $data;
    }

    /**
     * @param array{string?: string} $data
     *
     * @psalm-param array{string?: non-empty-string} $data
     * @psalm-suppress UnusedMethodCall
     */
    public function __unserialize(array $data): void
    {
        // @codeCoverageIgnoreStart
        if (!isset($data['string'])) {
            throw new ValueError(sprintf('%s(): Argument #1 ($data) is invalid', __METHOD__));
        }
        // @codeCoverageIgnoreEnd

        $this->unserialize($data['string']);
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getNumberConverter(): NumberConverterInterface
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getNumberConverter();
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress DeprecatedMethod
     */
    public function getFieldsHex(): array
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getFieldsHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getClockSeqHiAndReservedHex(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getClockSeqHiAndReservedHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getClockSeqLowHex(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getClockSeqLowHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getClockSequenceHex(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getClockSequenceHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getDateTime(): DateTimeInterface
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getDateTime();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getLeastSignificantBitsHex(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getLeastSignificantBitsHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getMostSignificantBitsHex(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getMostSignificantBitsHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getNodeHex(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getNodeHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getTimeHiAndVersionHex(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getTimeHiAndVersionHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getTimeLowHex(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getTimeLowHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getTimeMidHex(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getTimeMidHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getTimestampHex(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getTimestampHex();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getUrn(): string
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getUrn();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getVariant(): ?int
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getVariant();
    }

    /** @psalm-suppress DeprecatedMethod */
    public function getVersion(): ?int
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getVersion();
    }

    public function compareTo(UuidInterface $other): int
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->compareTo($other);
    }

    public function equals(?object $other): bool
    {
        if (! $other instanceof UuidInterface) {
            return false;
        }

        return $this->uuid === $other->toString();
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement we know that {@see self::$uuid} is a non-empty string, so
     *                                             we know that {@see hex2bin} will retrieve a non-empty string too.
     */
    public function getBytes(): string
    {
        if ($this->bytes === null) {
            /** @phpstan-ignore-next-line PHPStan complains that this is not a non-empty-string. */
            $this->bytes = (string) hex2bin(str_replace('-', '', $this->uuid));
        }
        return $this->bytes;
    }

    public function getFields(): FieldsInterface
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getFields();
    }

    public function getHex(): Hexadecimal
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getHex();
    }

    public function getInteger(): IntegerObject
    {
        return ($this->unwrapped ?? $this->unwrap())
            ->getInteger();
    }

    public function toString(): string
    {
        return $this->uuid;
    }

    public function __toString(): string
    {
        return $this->uuid;
    }

    public function jsonSerialize(): string
    {
        return $this->uuid;
    }

    /**
     * @psalm-suppress ImpureMethodCall the retrieval of the factory is a clear violation of purity here: this is a
     *                                  known pitfall of the design of this library, where a value object contains
     *                                  a mutable reference to a factory. We use a fixed factory here, so the violation
     *                                  will not have real-world effects, as this object is only instantiated with the
     *                                  default factory settings/features.
     * @psalm-suppress InaccessibleProperty property {@see $unwrapped} is used as a cache: we don't expose it to the
     *                                      outside world, so we should be fine here.
     */
    private function unwrap(): UuidInterface
    {
        return $this->unwrapped = FastUuidFactoryFactory::getFastUuidFactory()->fromString($this->uuid);
    }
}
