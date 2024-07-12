<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Test\Rfc4122;

use Mougrim\FastUuid\Fields\FastStringFields;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Rfc4122\Fields;
use Ramsey\Uuid\Type\Hexadecimal;

use Ramsey\Uuid\Uuid;
use function serialize;
use function unserialize;

/**
 * Based on https://github.com/ramsey/uuid/blob/4.7.6/tests/Rfc4122/FieldsTest.php
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

    #[DataProvider('nonRfc4122VariantProvider')]
    public function testNotRfc4122Variant(string $value, ?int $expectedVariant): void
    {
        $uuid = new FastStringFields($value);
        self::assertSame($expectedVariant, $uuid->getVariant());
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function nonRfc4122VariantProvider(): array
    {
        return [
            [
                'value' => 'ff6f8cb0-c57d-11e1-0b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-1b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-2b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-3b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-4b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-5b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-6b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-7b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-cb21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_MICROSOFT,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-db21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_MICROSOFT,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-eb21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_FUTURE,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-fb21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_FUTURE,
            ],
        ];
    }

    #[DataProvider('invalidVersionProvider')]
    public function testInvalidVersion(string $value, ?int $expectedVersion): void
    {
        $fields = new FastStringFields($value);
        self::assertSame($expectedVersion, $fields->getVersion());
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function invalidVersionProvider(): array
    {
        return [
            'version 0' => [
                'value' => 'ff6f8cb0-c57d-01e1-8b21-0800200c9a66',
                'expectedVersion' => 0,
            ],
            'version 9' => [
                'value' => 'ff6f8cb0-c57d-91e1-bb21-0800200c9a66',
                'expectedVersion' => 9,
            ],
            'version 10' => [
                'value' => 'ff6f8cb0-c57d-a1e1-9b21-0800200c9a66',
                'expectedVersion' => 10,
            ],
            'version 11' => [
                'value' => 'ff6f8cb0-c57d-b1e1-ab21-0800200c9a66',
                'expectedVersion' => 11,
            ],
            'version 12' => [
                'value' => 'ff6f8cb0-c57d-c1e1-ab21-0800200c9a66',
                'expectedVersion' => 12,
            ],
            'version 13' => [
                'value' => 'ff6f8cb0-c57d-d1e1-ab21-0800200c9a66',
                'expectedVersion' => 13,
            ],
            'version 14' => [
                'value' => 'ff6f8cb0-c57d-e1e1-ab21-0800200c9a66',
                'expectedVersion' => 14,
            ],
            'version 15' => [
                'value' => 'ff6f8cb0-c57d-f1e1-ab21-0800200c9a66',
                'expectedVersion' => 15,
            ],
        ];
    }

    #[DataProvider('fieldGetterMethodProvider')]
    public function testFieldGetterMethods(string $value, string $methodName, int|string|bool|null $expectedValue): void
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
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '1b21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '9b',
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '11e1',
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1e1c57dff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 1,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'value' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '2b21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => 'ab',
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '41e1',
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1e1c57dff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 4,
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'value' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '3b21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => 'bb',
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '31e1',
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1e1c57dff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 3,
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'value' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '0b21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '8b',
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '51e1',
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1e1c57dff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 5,
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'value' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '0b21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '8b',
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '61e1',
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => 'ff6f8cb0c57d1e1',
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 6,
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'value' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getClockSeq',
                'expectedValue' => '0000',
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '00',
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '00',
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getNode',
                'expectedValue' => '000000000000',
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '0000',
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getTimeLow',
                'expectedValue' => '00000000',
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getTimeMid',
                'expectedValue' => '0000',
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getTimestamp',
                'expectedValue' => '000000000000000',
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getVersion',
                'expectedValue' => null,
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'isNil',
                'expectedValue' => true,
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getClockSeq',
                'expectedValue' => 'ffff',
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => 'ff',
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => 'ff',
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getNode',
                'expectedValue' => 'ffffffffffff',
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => 'ffff',
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ffffffff',
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'ffff',
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getTimestamp',
                'expectedValue' => 'fffffffffffffff',
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getVersion',
                'expectedValue' => null,
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'isMax',
                'expectedValue' => true,
            ],

            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getClockSeq',
                'expectedValue' => '0400',
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '84',
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '00',
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getNode',
                'expectedValue' => '0242ac130003',
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '21ea',
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getTimeLow',
                'expectedValue' => '000001f5',
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getTimeMid',
                'expectedValue' => '5cde',
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1ea5cde00000000',
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getVersion',
                'expectedValue' => 2,
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'value' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '1b21',
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '9b',
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '71e1',
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => '018339f0',
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => '1b83',
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '000018339f01b83',
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 7,
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'value' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],
        ];
    }

    public function testSerializingFields(): void
    {
        $fields = new FastStringFields('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        $serializedFields = serialize($fields);

        /** @var Fields $unserializedFields */
        $unserializedFields = unserialize($serializedFields);

        $this->assertSame($fields->getBytes(), $unserializedFields->getBytes());
    }

    public function testSerializingFieldsWithOldFormat(): void
    {
        $fields = new FastStringFields("b3cd586a-e3ca-44f3-988c-f4d666c1bf4d");

        $serializedFields = 'C:26:"Ramsey\Uuid\Rfc4122\Fields":24:{s81YauPKRPOYjPTWZsG/TQ==}';

        /** @var Fields $unserializedFields */
        $unserializedFields = unserialize($serializedFields);

        $this->assertSame($fields->getBytes(), $unserializedFields->getBytes());
    }
}
