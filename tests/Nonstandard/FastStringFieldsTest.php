<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Test\Nonstandard;

use Mougrim\FastUuid\Fields\FastStringFields;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Nonstandard\Fields;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Uuid;

use function serialize;
use function unserialize;

/**
 * Based on https://github.com/ramsey/uuid/blob/4.7.6/tests/Nonstandard/FieldsTest.php
 */
class FastStringFieldsTest extends TestCase
{
    public function testConstructorThrowsExceptionIfNotSixteenByteString(): void
    {
        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage(
            'Invalid UUID string: foobar'
        );

        new FastStringFields('foobar');
    }

    #[DataProvider('fieldGetterMethodProvider')]
    public function testFieldGetterMethods(string $value, string $methodName, string|int|bool|null $expectedValue): void
    {
        $fields = new FastStringFields($value);

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
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '0b21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '0b',
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '91e1',
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1e1c57dff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => Uuid::RESERVED_NCS,
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => null,
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'value' => 'ff6f8cb0-c57d-91e1-0b21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],
        ];
    }

    public function testSerializingFields(): void
    {
        $fields = new FastStringFields('ff6f8cb0-c57d-91e1-0b21-0800200c9a66');

        $serializedFields = serialize($fields);

        /** @var Fields $unserializedFields */
        $unserializedFields = unserialize($serializedFields);

        $this->assertSame($fields->getBytes(), $unserializedFields->getBytes());
    }
}
