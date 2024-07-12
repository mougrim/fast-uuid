<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Test\Nonstandard;

use Mougrim\FastUuid\Fields\FastBytesFields;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Nonstandard\Fields;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Uuid;

use function hex2bin;
use function serialize;
use function str_replace;
use function unserialize;

/**
 * Based on https://github.com/ramsey/uuid/blob/4.7.6/tests/Nonstandard/FieldsTest.php
 */
class FastBytesFieldsTest extends TestCase
{
    public function testConstructorThrowsExceptionIfNotSixteenByteString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The byte string must be 16 bytes long; received 6 bytes'
        );

        new FastBytesFields('foobar');
    }

    #[DataProvider('fieldGetterMethodProvider')]
    public function testFieldGetterMethods(string $uuid, string $methodName, string|int|bool|null $expectedValue): void
    {
        $bytes = (string) hex2bin(str_replace('-', '', $uuid));
        $fields = new FastBytesFields($bytes);

        $result = $fields->$methodName();

        if ($result instanceof Hexadecimal) {
            $this->assertSame($expectedValue, $result->toString());
        } else {
            $this->assertSame($expectedValue, $result);
        }
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function fieldGetterMethodProvider(): array
    {
        return [
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '0b21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '0b',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '91e1',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1e1c57dff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => Uuid::RESERVED_NCS,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => null,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],
        ];
    }

    public function testSerializingFields(): void
    {
        $bytes = (string) hex2bin(str_replace('-', '', 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66'));
        $fields = new FastBytesFields($bytes);

        $serializedFields = serialize($fields);

        /** @var Fields $unserializedFields */
        $unserializedFields = unserialize($serializedFields);

        $this->assertSame($fields->getBytes(), $unserializedFields->getBytes());
    }
}
