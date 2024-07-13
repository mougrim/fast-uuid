<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Uuid;

use Brick\Math\Internal\Calculator;
use Mougrim\FastUuid\Factory\FastUuidFactoryFactory;
use Mougrim\FastUuid\Fields\FastBytesFields;
use Ramsey\Uuid\Codec\CodecInterface;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;
use Ramsey\Uuid\UuidInterface;
use ValueError;
use function bin2hex;
use function ltrim;
use function sprintf;
use function strcmp;

/**
 * @author Mougrim <for-open-source@mougrim.io>
 */
class FastBytesUuid implements UuidInterface
{
    use TimeTrait;

    private ?Hexadecimal $hex = null;
    private ?IntegerObject $integer = null;
    private ?string $string = null;

    public function __construct(
        protected FastBytesFields $fields,
        protected CodecInterface $codec,
        protected NumberConverterInterface $numberConverter,
        protected TimeConverterInterface $timeConverter,
    ) {
    }

    /**
     * @psalm-return non-empty-string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Converts the UUID to a string for JSON serialization
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * Converts the UUID to a string for PHP serialization
     */
    public function serialize(): string
    {
        return $this->fields->getBytes();
    }

    /**
     * @return array{bytes: string}
     */
    public function __serialize(): array
    {
        return ['bytes' => $this->serialize()];
    }

    /**
     * Re-constructs the object from its serialized form
     *
     * @param string $data The serialized PHP string to unserialize into
     *     a UuidInterface instance
     */
    public function unserialize(string $data): void
    {
        $this->hex = null;
        $this->integer = null;
        $this->string = null;
        $this->dateTime = null;

        /** @var FastBytesUuid $uuid */
        $uuid = FastUuidFactoryFactory::getFastUuidFactory()->fromBytes($data);

        $this->codec = $uuid->codec;
        $this->numberConverter = $uuid->numberConverter;
        $this->fields = $uuid->fields;
        $this->timeConverter = $uuid->timeConverter;
    }

    /**
     * @param array{bytes?: string} $data
     */
    public function __unserialize(array $data): void
    {
        if (!isset($data['bytes'])) {
            throw new ValueError(sprintf('%s(): Argument #1 ($data) is invalid', __METHOD__));
        }

        $this->unserialize($data['bytes']);
    }

    public function compareTo(UuidInterface $other): int
    {
        $compare = strcmp($this->getBytes(), $other->getBytes());

        if ($compare < 0) {
            return -1;
        }

        if ($compare > 0) {
            return 1;
        }

        return 0;
    }

    public function equals(?object $other): bool
    {
        if (!$other instanceof UuidInterface) {
            return false;
        }

        return $this->compareTo($other) === 0;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function getBytes(): string
    {
        return $this->fields->getBytes();
    }

    public function getFields(): FastBytesFields
    {
        return $this->fields;
    }

    public function getHex(): Hexadecimal
    {
        if ($this->hex === null) {
            $this->hex = new Hexadecimal(bin2hex($this->fields->getBytes()));
        }

        return $this->hex;
    }

    public function getInteger(): IntegerObject
    {
        if ($this->integer === null) {
            $this->integer = $this->calculateInteger();
        }

        return $this->integer;
    }

    public function calculateInteger(): IntegerObject
    {
        $hex = ltrim($this->getHex()->toString(), '0');
        if ($hex === '') {
            return new IntegerObject('0');
        }

        /** @noinspection PhpInternalEntityUsedInspection */
        return new IntegerObject(Calculator::get()->fromBase($hex, 16));
    }

    public function getUrn(): string
    {
        return 'urn:uuid:' . $this->toString();
    }

    /**
     * @psalm-return non-empty-string
     */
    public function toString(): string
    {
        if ($this->string === null) {
            $this->string = $this->codec->encode($this);
        }

        return $this->string;
    }
}
