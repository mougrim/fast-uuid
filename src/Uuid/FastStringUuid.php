<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Uuid;

use Mougrim\FastUuid\Factory\FastUuidFactoryFactory;
use Mougrim\FastUuid\Fields\FastStringFields;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerObject;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use ValueError;
use function sprintf;
use function str_replace;
use function strcmp;

/**
 * @author Mougrim <for-open-source@mougrim.io>
 */
class FastStringUuid implements UuidInterface
{
    use TimeTrait;

    private ?Hexadecimal $hex = null;
    private ?IntegerObject $integer = null;

    public function __construct(
        protected FastStringFields $fields,
        protected NumberConverterInterface $numberConverter,
        protected TimeConverterInterface $timeConverter,
        TimeConverterInterface $unixTimeConverter,
    ) {
        if ($this->fields->getVersion() === Uuid::UUID_TYPE_UNIX_TIME) {
            $this->timeConverter = $unixTimeConverter;
        }
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
        return $this->fields->getValue();
    }

    /**
     * @return array{value: string}
     */
    public function __serialize(): array
    {
        return ['value' => $this->serialize()];
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
        $this->dateTime = null;

        /** @var FastStringUuid $uuid */
        $uuid = FastUuidFactoryFactory::getFastUuidFactory()->fromString($data);

        $this->numberConverter = $uuid->numberConverter;
        $this->fields = $uuid->fields;
        $this->timeConverter = $uuid->timeConverter;
    }

    /**
     * @param array{value?: string} $data
     */
    public function __unserialize(array $data): void
    {
        // @codeCoverageIgnoreStart
        if (!isset($data['value'])) {
            throw new ValueError(sprintf('%s(): Argument #1 ($data) is invalid', __METHOD__));
        }
        // @codeCoverageIgnoreEnd

        $this->unserialize($data['value']);
    }

    public function compareTo(UuidInterface $other): int
    {
        $compare = strcmp($this->toString(), $other->toString());

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

    public function getFields(): FastStringFields
    {
        return $this->fields;
    }

    public function getHex(): Hexadecimal
    {
        if ($this->hex === null) {
            $this->hex = new Hexadecimal(str_replace('-', '', $this->toString()));
        }

        return $this->hex;
    }

    public function getInteger(): IntegerObject
    {
        if ($this->integer === null) {
            $this->integer = new IntegerObject($this->numberConverter->fromHex($this->getHex()->toString()));
        }

        return $this->integer;
    }

    public function getUrn(): string
    {
        return 'urn:uuid:' . $this->toString();
    }

    public function toString(): string
    {
        return $this->fields->getValue();
    }
}
