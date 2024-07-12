<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Test\Rfc4122;

use Mougrim\FastUuid\Fields\FastBytesFields;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Rfc4122\Fields;
use Ramsey\Uuid\Type\Hexadecimal;

use Ramsey\Uuid\Uuid;
use function hex2bin;
use function serialize;
use function str_replace;
use function unserialize;

/**
 * Based on https://github.com/ramsey/uuid/blob/4.7.6/tests/Rfc4122/FieldsTest.php
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

    #[DataProvider('nonRfc4122VariantProvider')]
    public function testNotRfc4122Variant(string $uuid, ?int $expectedVariant): void
    {
        $bytes = (string) hex2bin(str_replace('-', '', $uuid));

        $uuid = new FastBytesFields($bytes);
        self::assertSame($expectedVariant, $uuid->getVariant());
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function nonRfc4122VariantProvider(): array
    {
        return [
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-0b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-1b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-2b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-3b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-4b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-5b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-6b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-7b21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_NCS,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-cb21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_MICROSOFT,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-db21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_MICROSOFT,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-eb21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_FUTURE,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-fb21-0800200c9a66',
                'expectedVariant' => Uuid::RESERVED_FUTURE,
            ],
        ];
    }

    #[DataProvider('invalidVersionProvider')]
    public function testInvalidVersion(string $uuid, ?int $expectedVersion): void
    {
        $bytes = (string) hex2bin(str_replace('-', '', $uuid));

        $fields = new FastBytesFields($bytes);
        self::assertSame($expectedVersion, $fields->getVersion());
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function invalidVersionProvider(): array
    {
        return [
            'version 0' => [
                'uuid' => 'ff6f8cb0-c57d-01e1-8b21-0800200c9a66',
                'expectedVersion' => 0,
            ],
            'version 9' => [
                'uuid' => 'ff6f8cb0-c57d-91e1-bb21-0800200c9a66',
                'expectedVersion' => 9,
            ],
            'version 10' => [
                'uuid' => 'ff6f8cb0-c57d-a1e1-9b21-0800200c9a66',
                'expectedVersion' => 10,
            ],
            'version 11' => [
                'uuid' => 'ff6f8cb0-c57d-b1e1-ab21-0800200c9a66',
                'expectedVersion' => 11,
            ],
            'version 12' => [
                'uuid' => 'ff6f8cb0-c57d-c1e1-ab21-0800200c9a66',
                'expectedVersion' => 12,
            ],
            'version 13' => [
                'uuid' => 'ff6f8cb0-c57d-d1e1-ab21-0800200c9a66',
                'expectedVersion' => 13,
            ],
            'version 14' => [
                'uuid' => 'ff6f8cb0-c57d-e1e1-ab21-0800200c9a66',
                'expectedVersion' => 14,
            ],
            'version 15' => [
                'uuid' => 'ff6f8cb0-c57d-f1e1-ab21-0800200c9a66',
                'expectedVersion' => 15,
            ],
        ];
    }

    #[DataProvider('fieldGetterMethodProvider')]
    public function testFieldGetterMethods(string $uuid, string $methodName, int|string|bool|null $expectedValue): void
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
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '1b21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '9b',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '11e1',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1e1c57dff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 1,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '2b21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => 'ab',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '41e1',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1e1c57dff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 4,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '3b21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => 'bb',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '31e1',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1e1c57dff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 3,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '0b21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '8b',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '51e1',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1e1c57dff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 5,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '0b21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '8b',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '61e1',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ff6f8cb0',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'c57d',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => 'ff6f8cb0c57d1e1',
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 6,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'uuid' => 'ff6f8cb0-c57d-61e1-8b21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getClockSeq',
                'expectedValue' => '0000',
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '00',
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '00',
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getNode',
                'expectedValue' => '000000000000',
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '0000',
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getTimeLow',
                'expectedValue' => '00000000',
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getTimeMid',
                'expectedValue' => '0000',
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getTimestamp',
                'expectedValue' => '000000000000000',
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'getVersion',
                'expectedValue' => null,
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'isNil',
                'expectedValue' => true,
            ],
            [
                'uuid' => '00000000-0000-0000-0000-000000000000',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getClockSeq',
                'expectedValue' => 'ffff',
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => 'ff',
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => 'ff',
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getNode',
                'expectedValue' => 'ffffffffffff',
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => 'ffff',
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getTimeLow',
                'expectedValue' => 'ffffffff',
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getTimeMid',
                'expectedValue' => 'ffff',
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getTimestamp',
                'expectedValue' => 'fffffffffffffff',
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'getVersion',
                'expectedValue' => null,
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'uuid' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'methodName' => 'isMax',
                'expectedValue' => true,
            ],

            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getClockSeq',
                'expectedValue' => '0400',
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '84',
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '00',
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getNode',
                'expectedValue' => '0242ac130003',
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '21ea',
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getTimeLow',
                'expectedValue' => '000001f5',
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getTimeMid',
                'expectedValue' => '5cde',
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getTimestamp',
                'expectedValue' => '1ea5cde00000000',
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'getVersion',
                'expectedValue' => 2,
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'uuid' => '000001f5-5cde-21ea-8400-0242ac130003',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],

            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeq',
                'expectedValue' => '1b21',
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeqHiAndReserved',
                'expectedValue' => '9b',
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getClockSeqLow',
                'expectedValue' => '21',
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getNode',
                'expectedValue' => '0800200c9a66',
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getTimeHiAndVersion',
                'expectedValue' => '71e1',
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getTimeLow',
                'expectedValue' => '018339f0',
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getTimeMid',
                'expectedValue' => '1b83',
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getTimestamp',
                'expectedValue' => '000018339f01b83',
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getVariant',
                'expectedValue' => 2,
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'getVersion',
                'expectedValue' => 7,
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'isNil',
                'expectedValue' => false,
            ],
            [
                'uuid' => '018339f0-1b83-71e1-9b21-0800200c9a66',
                'methodName' => 'isMax',
                'expectedValue' => false,
            ],
        ];
    }

    public function testSerializingFields(): void
    {
        $bytes = (string) hex2bin(str_replace('-', '', 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66'));
        $fields = new FastBytesFields($bytes);

        $serializedFields = serialize($fields);

        /** @var Fields $unserializedFields */
        $unserializedFields = unserialize($serializedFields);

        $this->assertSame($fields->getBytes(), $unserializedFields->getBytes());
    }

    public function testSerializingFieldsWithOldFormat(): void
    {
        $fields = new FastBytesFields("\xb3\xcd\x58\x6a\xe3\xca\x44\xf3\x98\x8c\xf4\xd6\x66\xc1\xbf\x4d");

        $serializedFields = 'C:26:"Ramsey\Uuid\Rfc4122\Fields":24:{s81YauPKRPOYjPTWZsG/TQ==}';

        /** @var Fields $unserializedFields */
        $unserializedFields = unserialize($serializedFields);

        $this->assertSame($fields->getBytes(), $unserializedFields->getBytes());
    }
}
