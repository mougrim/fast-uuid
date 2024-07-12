<?php
/** @noinspection PhpDeprecationInspection */
declare(strict_types=1);

namespace Mougrim\FastUuid\Test;

use Brick\Math\BigInteger;
use Mougrim\FastUuid\Factory\FastUuidFactory;
use Mougrim\FastUuid\Factory\FastUuidFactoryFactory;
use Mougrim\FastUuid\Uuid\FastBytesUuid;
use Mougrim\FastUuid\Uuid\FastStringUuid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Codec\OrderedTimeCodec;
use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\Generator\DefaultTimeGenerator;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Time;
use Ramsey\Uuid\Uuid;
use stdClass;
use Ramsey\Uuid\UuidInterface;
use function hex2bin;
use Ramsey\Uuid\Generator\RandomGeneratorInterface;
use Ramsey\Uuid\Generator\TimeGeneratorInterface;
use Ramsey\Uuid\Provider\NodeProviderInterface;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Provider\TimeProviderInterface;
use const JSON_THROW_ON_ERROR;

/**
 * Based on https://github.com/ramsey/uuid/blob/4.7.6/tests/ExpectedBehaviorTest.php
 *
 * These tests exist to ensure a seamless upgrade path from 3.x to 4.x. If any
 * of these tests fail in 4.x, then it's because we've changed functionality
 * in such a way that compatibility with 3.x is broken.
 *
 * Naturally, there are some BC-breaks between 3.x and 4.x, but these tests
 * ensure that the base-level functionality that satisfies 80% of use-cases
 * does not change. The remaining 20% of use-cases should refer to the README
 * for details on the easiest path to transition from 3.x to 4.x.
 */
class ExpectedBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory();
    }

    #[DataProvider('provideStaticCreationMethods')]
    public function testStaticCreationMethodsAndStandardBehavior($method, $args): void
    {
        $uuid = call_user_func_array([Uuid::class, $method], $args);

        $this->assertInstanceOf(UuidInterface::class, $uuid);
        $this->assertIsInt($uuid->compareTo(Uuid::uuid1()));
        $this->assertNotSame(0, $uuid->compareTo(Uuid::uuid4()));
        $this->assertSame(0, $uuid->compareTo(clone $uuid));
        $this->assertFalse($uuid->equals(new stdClass()));
        $this->assertTrue($uuid->equals(clone $uuid));
        $this->assertIsString($uuid->getBytes());
        // check that it doesn't throw exception
        $uuid->getNumberConverter();
        $this->assertIsString((string) $uuid->getHex());
        $this->assertIsArray($uuid->getFieldsHex());
        $this->assertArrayHasKey('time_low', $uuid->getFieldsHex());
        $this->assertArrayHasKey('time_mid', $uuid->getFieldsHex());
        $this->assertArrayHasKey('time_hi_and_version', $uuid->getFieldsHex());
        $this->assertArrayHasKey('clock_seq_hi_and_reserved', $uuid->getFieldsHex());
        $this->assertArrayHasKey('clock_seq_low', $uuid->getFieldsHex());
        $this->assertArrayHasKey('node', $uuid->getFieldsHex());
        $this->assertIsString($uuid->getTimeLowHex());
        $this->assertIsString($uuid->getTimeMidHex());
        $this->assertIsString($uuid->getTimeHiAndVersionHex());
        $this->assertIsString($uuid->getClockSeqHiAndReservedHex());
        $this->assertIsString($uuid->getClockSeqLowHex());
        $this->assertIsString($uuid->getNodeHex());
        $this->assertSame($uuid->getFieldsHex()['time_low'], $uuid->getTimeLowHex());
        $this->assertSame($uuid->getFieldsHex()['time_mid'], $uuid->getTimeMidHex());
        $this->assertSame($uuid->getFieldsHex()['time_hi_and_version'], $uuid->getTimeHiAndVersionHex());
        $this->assertSame($uuid->getFieldsHex()['clock_seq_hi_and_reserved'], $uuid->getClockSeqHiAndReservedHex());
        $this->assertSame($uuid->getFieldsHex()['clock_seq_low'], $uuid->getClockSeqLowHex());
        $this->assertSame($uuid->getFieldsHex()['node'], $uuid->getNodeHex());
        $this->assertSame(substr((string) $uuid->getHex(), 16), $uuid->getLeastSignificantBitsHex());
        $this->assertSame(substr((string) $uuid->getHex(), 0, 16), $uuid->getMostSignificantBitsHex());

        $this->assertSame(
            (string) $uuid->getHex(),
            $uuid->getTimeLowHex()
                . $uuid->getTimeMidHex()
                . $uuid->getTimeHiAndVersionHex()
                . $uuid->getClockSeqHiAndReservedHex()
                . $uuid->getClockSeqLowHex()
                . $uuid->getNodeHex()
        );

        $this->assertSame(
            (string) $uuid->getHex(),
            $uuid->getFieldsHex()['time_low']
                . $uuid->getFieldsHex()['time_mid']
                . $uuid->getFieldsHex()['time_hi_and_version']
                . $uuid->getFieldsHex()['clock_seq_hi_and_reserved']
                . $uuid->getFieldsHex()['clock_seq_low']
                . $uuid->getFieldsHex()['node']
        );

        $this->assertIsString($uuid->getUrn());
        $this->assertStringStartsWith('urn:uuid:', $uuid->getUrn());
        $this->assertSame('urn:uuid:' . $uuid->getHex(), str_replace('-', '', $uuid->getUrn()));
        $this->assertSame((string) $uuid->getHex(), str_replace('-', '', $uuid->toString()));
        $this->assertSame((string) $uuid->getHex(), str_replace('-', '', (string) $uuid));

        $this->assertSame(
            $uuid->toString(),
            $uuid->getTimeLowHex() . '-'
                . $uuid->getTimeMidHex() . '-'
                . $uuid->getTimeHiAndVersionHex() . '-'
                . $uuid->getClockSeqHiAndReservedHex()
                . $uuid->getClockSeqLowHex() . '-'
                . $uuid->getNodeHex()
        );

        $this->assertSame(
            (string) $uuid,
            $uuid->getTimeLowHex() . '-'
                . $uuid->getTimeMidHex() . '-'
                . $uuid->getTimeHiAndVersionHex() . '-'
                . $uuid->getClockSeqHiAndReservedHex()
                . $uuid->getClockSeqLowHex() . '-'
                . $uuid->getNodeHex()
        );

        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame((int) substr($method, -1), $uuid->getVersion());
        $this->assertMatchesRegularExpression('/^\d+$/', (string) $uuid->getInteger());
    }

    public static function provideStaticCreationMethods(): array
    {
        return [
            ['uuid1', []],
            ['uuid1', ['00000fffffff']],
            ['uuid1', [null, 1234]],
            ['uuid1', ['00000fffffff', 1234]],
            ['uuid1', ['00000fffffff', null]],
            ['uuid1', [268435455]],
            ['uuid1', [268435455, 1234]],
            ['uuid1', [268435455, null]],
            ['uuid3', [Uuid::NAMESPACE_URL, 'https://example.com/foo']],
            ['uuid4', []],
            ['uuid5', [Uuid::NAMESPACE_URL, 'https://example.com/foo']],
        ];
    }

    public function testUuidVersion1MethodBehavior(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::uuid1('00000fffffff', 0xffff);

        // check that it doesn't throw exception
        $uuid->getDateTime();
        $this->assertSame('00000fffffff', $uuid->getNodeHex());
        $this->assertSame('3fff', $uuid->getClockSequenceHex());
        $this->assertSame('16383', $uuid->getClockSequence());
    }

    public function testUuidVersion1MethodBehavior64Bit(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::uuid1('ffffffffffff', 0xffff);

        // check that it doesn't throw exception
        $uuid->getDateTime();
        $this->assertSame('ffffffffffff', $uuid->getNodeHex());
        $this->assertSame('281474976710655', $uuid->getNode());
        $this->assertSame('3fff', $uuid->getClockSequenceHex());
        $this->assertSame('16383', $uuid->getClockSequence());
        $this->assertMatchesRegularExpression('/^\d+$/', $uuid->getTimestamp());
    }

    #[DataProvider('provideIsValid')]
    public function testIsValid($uuid, $expected): void
    {
        $this->assertSame($expected, Uuid::isValid((string) $uuid), "{$uuid} is not a valid UUID");
        $this->assertSame(
            $expected,
            Uuid::isValid(strtoupper((string) $uuid)),
            strtoupper((string) $uuid) . ' is not a valid UUID',
        );
    }

    public static function provideIsValid(): array
    {
        return [
            // RFC 4122 UUIDs
            ['00000000-0000-0000-0000-000000000000', true],
            ['ff6f8cb0-c57d-11e1-8b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-11e1-9b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-11e1-ab21-0800200c9a66', true],
            ['ff6f8cb0-c57d-11e1-bb21-0800200c9a66', true],
            ['ff6f8cb0-c57d-21e1-8b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-21e1-9b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-21e1-ab21-0800200c9a66', true],
            ['ff6f8cb0-c57d-21e1-bb21-0800200c9a66', true],
            ['ff6f8cb0-c57d-31e1-8b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-31e1-9b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-31e1-ab21-0800200c9a66', true],
            ['ff6f8cb0-c57d-31e1-bb21-0800200c9a66', true],
            ['ff6f8cb0-c57d-41e1-8b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-41e1-9b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-41e1-ab21-0800200c9a66', true],
            ['ff6f8cb0-c57d-41e1-bb21-0800200c9a66', true],
            ['ff6f8cb0-c57d-51e1-8b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-51e1-9b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-51e1-ab21-0800200c9a66', true],
            ['ff6f8cb0-c57d-51e1-bb21-0800200c9a66', true],

            // Non RFC 4122 UUIDs
            ['ffffffff-ffff-ffff-ffff-ffffffffffff', true],
            ['00000000-0000-0000-0000-000000000000', true],
            ['ff6f8cb0-c57d-01e1-0b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-01e1-1b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-01e1-2b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-01e1-3b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-01e1-4b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-01e1-5b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-01e1-6b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-01e1-7b21-0800200c9a66', true],
            ['ff6f8cb0-c57d-01e1-db21-0800200c9a66', true],
            ['ff6f8cb0-c57d-01e1-eb21-0800200c9a66', true],
            ['ff6f8cb0-c57d-01e1-fb21-0800200c9a66', true],

            // Other valid patterns
            ['{ff6f8cb0-c57d-01e1-fb21-0800200c9a66}', true],
            ['urn:uuid:ff6f8cb0-c57d-01e1-fb21-0800200c9a66', true],

            // Invalid UUIDs
            ['ffffffffffffffffffffffffffffffff', false],
            ['00000000000000000000000000000000', false],
            [0, false],
            ['foobar', false],
            ['ff6f8cb0c57d51e1bb210800200c9a66', false],
            ['gf6f8cb0-c57d-51e1-bb21-0800200c9a66', false],
        ];
    }

    /** @noinspection PhpUnusedParameterInspection */
    #[DataProvider('provideFromStringInteger')]
    public function testSerialization($string, $version, $variant, $integer): void
    {
        $uuid = Uuid::fromString($string);

        $serialized = serialize($uuid);
        $unserialized = unserialize($serialized);

        $this->assertSame(0, $uuid->compareTo($unserialized));
        $this->assertTrue($uuid->equals($unserialized));
        $this->assertSame("\"{$string}\"", json_encode($uuid, flags: JSON_THROW_ON_ERROR));
    }

    /** @noinspection PhpUnusedParameterInspection */
    #[DataProvider('provideFromStringInteger')]
    public function testSerializationWithOrderedTimeCodec($string, $version, $variant, $integer): void
    {
        $features = new FeatureSet();
        $factory = (new FastUuidFactoryFactory())->create(features: $features, codec: new OrderedTimeCodec(
            $features->getBuilder()
        ));

        $previousFactory = Uuid::getFactory();
        Uuid::setFactory($factory);
        $uuid = Uuid::fromString($string);

        $serialized = serialize($uuid);
        $unserialized = unserialize($serialized);

        Uuid::setFactory($previousFactory);

        $this->assertSame(0, $uuid->compareTo($unserialized));
        $this->assertTrue($uuid->equals($unserialized));
        $this->assertSame("\"{$string}\"", json_encode($uuid, flags: JSON_THROW_ON_ERROR));
    }

    /** @noinspection PhpUnusedParameterInspection */
    #[DataProvider('provideFromStringInteger')]
    public function testNumericReturnValues($string, $version, $variant, $integer): void
    {
        $leastSignificantBitsHex = substr(str_replace('-', '', $string), 16);
        $mostSignificantBitsHex = substr(str_replace('-', '', $string), 0, 16);
        $leastSignificantBits = BigInteger::fromBase($leastSignificantBitsHex, 16)->__toString();
        $mostSignificantBits = BigInteger::fromBase($mostSignificantBitsHex, 16)->__toString();

        $components = explode('-', $string);
        array_walk($components, static function (&$value) {
            $value = BigInteger::fromBase($value, 16)->__toString();
        });

        if (strtolower($string) === Uuid::MAX) {
            $clockSeq = (int) $components[3];
        } else {
            $clockSeq = (int) $components[3] & 0x3fff;
        }

        $clockSeqHiAndReserved = (int) $components[3] >> 8;
        $clockSeqLow = (int) $components[3] & 0x00ff;

        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString($string);

        $this->assertSame($components[0], $uuid->getTimeLow());
        $this->assertSame($components[1], $uuid->getTimeMid());
        $this->assertSame($components[2], $uuid->getTimeHiAndVersion());
        $this->assertSame((string) $clockSeq, $uuid->getClockSequence());
        $this->assertSame((string) $clockSeqHiAndReserved, $uuid->getClockSeqHiAndReserved());
        $this->assertSame((string) $clockSeqLow, $uuid->getClockSeqLow());
        $this->assertSame($components[4], $uuid->getNode());
        $this->assertSame($leastSignificantBits, $uuid->getLeastSignificantBits());
        $this->assertSame($mostSignificantBits, $uuid->getMostSignificantBits());
    }

    #[DataProvider('provideFromStringInteger')]
    public function testFromBytes($string, $version, $variant, $integer): void
    {
        $bytes = hex2bin(str_replace('-', '', $string));

        $uuid = Uuid::fromBytes($bytes);

        $this->assertSame($string, $uuid->toString());
        $this->assertSame($version, $uuid->getVersion());
        $this->assertSame($variant, $uuid->getVariant());

        $components = explode('-', $string);

        $this->assertSame($components[0], $uuid->getTimeLowHex());
        $this->assertSame($components[1], $uuid->getTimeMidHex());
        $this->assertSame($components[2], $uuid->getTimeHiAndVersionHex());
        $this->assertSame($components[3], $uuid->getClockSeqHiAndReservedHex() . $uuid->getClockSeqLowHex());
        $this->assertSame($components[4], $uuid->getNodeHex());
        $this->assertSame($integer, (string) $uuid->getInteger());
        $this->assertSame($bytes, $uuid->getBytes());
    }

    #[DataProvider('provideFromStringInteger')]
    public function testFromInteger($string, $version, $variant, $integer): void
    {
        $bytes = hex2bin(str_replace('-', '', $string));

        $uuid = Uuid::fromInteger($integer);

        $this->assertSame($string, $uuid->toString());
        $this->assertSame($version, $uuid->getVersion());
        $this->assertSame($variant, $uuid->getVariant());

        $components = explode('-', $string);

        $this->assertSame($components[0], $uuid->getTimeLowHex());
        $this->assertSame($components[1], $uuid->getTimeMidHex());
        $this->assertSame($components[2], $uuid->getTimeHiAndVersionHex());
        $this->assertSame($components[3], $uuid->getClockSeqHiAndReservedHex() . $uuid->getClockSeqLowHex());
        $this->assertSame($components[4], $uuid->getNodeHex());
        $this->assertSame($integer, (string) $uuid->getInteger());
        $this->assertSame($bytes, $uuid->getBytes());
    }

    #[DataProvider('provideFromStringInteger')]
    public function testFromString($string, $version, $variant, $integer): void
    {
        $bytes = hex2bin(str_replace('-', '', $string));

        $uuid = Uuid::fromString($string);

        $this->assertSame($string, $uuid->toString());
        $this->assertSame($version, $uuid->getVersion());
        $this->assertSame($variant, $uuid->getVariant());

        $components = explode('-', $string);

        $this->assertSame($components[0], $uuid->getTimeLowHex());
        $this->assertSame($components[1], $uuid->getTimeMidHex());
        $this->assertSame($components[2], $uuid->getTimeHiAndVersionHex());
        $this->assertSame($components[3], $uuid->getClockSeqHiAndReservedHex() . $uuid->getClockSeqLowHex());
        $this->assertSame($components[4], $uuid->getNodeHex());
        $this->assertSame($integer, (string) $uuid->getInteger());
        $this->assertSame($bytes, $uuid->getBytes());
    }

    public static function provideFromStringInteger(): array
    {
        return [
            [
                'string' => '00000000-0000-0000-0000-000000000000',
                'version' => null,
                'variant' => Uuid::RFC_4122,
                'integer' => '0',
            ],
            [
                'string' => 'ff6f8cb0-c57d-11e1-8b21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_TIME,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071774304650190139318639206',
            ],
            [
                'string' => 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_TIME,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071774305803111643925486182',
            ],
            [
                'string' => 'ff6f8cb0-c57d-11e1-ab21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_TIME,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071774306956033148532333158',
            ],
            [
                'string' => 'ff6f8cb0-c57d-11e1-bb21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_TIME,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071774308108954653139180134',
            ],
            [
                'string' => 'ff6f8cb0-c57d-21e1-8b21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_DCE_SECURITY,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071849862513916053642058342',
            ],
            [
                'string' => 'ff6f8cb0-c57d-21e1-9b21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_DCE_SECURITY,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071849863666837558248905318',
            ],
            [
                'string' => 'ff6f8cb0-c57d-21e1-ab21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_DCE_SECURITY,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071849864819759062855752294',
            ],
            [
                'string' => 'ff6f8cb0-c57d-21e1-bb21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_DCE_SECURITY,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071849865972680567462599270',
            ],
            [
                'string' => 'ff6f8cb0-c57d-31e1-8b21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_HASH_MD5,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071925420377641967965477478',
            ],
            [
                'string' => 'ff6f8cb0-c57d-31e1-9b21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_HASH_MD5,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071925421530563472572324454',
            ],
            [
                'string' => 'ff6f8cb0-c57d-31e1-ab21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_HASH_MD5,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071925422683484977179171430',
            ],
            [
                'string' => 'ff6f8cb0-c57d-31e1-bb21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_HASH_MD5,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419071925423836406481786018406',
            ],
            [
                'string' => 'ff6f8cb0-c57d-41e1-8b21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_RANDOM,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419072000978241367882288896614',
            ],
            [
                'string' => 'ff6f8cb0-c57d-41e1-9b21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_RANDOM,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419072000979394289386895743590',
            ],
            [
                'string' => 'ff6f8cb0-c57d-41e1-ab21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_RANDOM,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419072000980547210891502590566',
            ],
            [
                'string' => 'ff6f8cb0-c57d-41e1-bb21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_RANDOM,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419072000981700132396109437542',
            ],
            [
                'string' => 'ff6f8cb0-c57d-51e1-8b21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_HASH_SHA1,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419072076536105093796612315750',
            ],
            [
                'string' => 'ff6f8cb0-c57d-51e1-9b21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_HASH_SHA1,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419072076537258015301219162726',
            ],
            [
                'string' => 'ff6f8cb0-c57d-51e1-ab21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_HASH_SHA1,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419072076538410936805826009702',
            ],
            [
                'string' => 'ff6f8cb0-c57d-51e1-bb21-0800200c9a66',
                'version' => Uuid::UUID_TYPE_HASH_SHA1,
                'variant' => Uuid::RFC_4122,
                'integer' => '339532337419072076539563858310432856678',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-0b21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_NCS,
                'integer' => '339532337419071698737563092188140444262',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-1b21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_NCS,
                'integer' => '339532337419071698738716013692747291238',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-2b21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_NCS,
                'integer' => '339532337419071698739868935197354138214',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-3b21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_NCS,
                'integer' => '339532337419071698741021856701960985190',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-4b21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_NCS,
                'integer' => '339532337419071698742174778206567832166',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-5b21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_NCS,
                'integer' => '339532337419071698743327699711174679142',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-6b21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_NCS,
                'integer' => '339532337419071698744480621215781526118',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-7b21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_NCS,
                'integer' => '339532337419071698745633542720388373094',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-cb21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_MICROSOFT,
                'integer' => '339532337419071698751398150243422607974',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-db21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_MICROSOFT,
                'integer' => '339532337419071698752551071748029454950',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-eb21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_FUTURE,
                'integer' => '339532337419071698753703993252636301926',
            ],
            [
                'string' => 'ff6f8cb0-c57d-01e1-fb21-0800200c9a66',
                'version' => null,
                'variant' => Uuid::RESERVED_FUTURE,
                'integer' => '339532337419071698754856914757243148902',
            ],
            [
                'string' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'version' => null,
                'variant' => Uuid::RFC_4122,
                'integer' => '340282366920938463463374607431768211455',
            ],
        ];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGetSetFactory(): void
    {
        $this->assertInstanceOf(FastUuidFactory::class, Uuid::getFactory());

        $factory = $this->createMock(FastUuidFactory::class);
        Uuid::setFactory($factory);

        $this->assertSame($factory, Uuid::getFactory());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUsingCustomRandomGenerator(): void
    {
        $generator = $this->createMock(RandomGeneratorInterface::class);
        $generator->method('generate')->willReturn(hex2bin('01234567abcd5432dcba0123456789ab'));

        $factory = (new FastUuidFactoryFactory())->create(randomGenerator: $generator);

        Uuid::setFactory($factory);

        $uuid = Uuid::uuid4();

        $this->assertSame('01234567-abcd-4432-9cba-0123456789ab', $uuid->toString());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUsingCustomTimeGenerator(): void
    {
        $generator = $this->createMock(TimeGeneratorInterface::class);
        $generator->method('generate')->willReturn(hex2bin('01234567abcd5432dcba0123456789ab'));

        $factory = (new FastUuidFactoryFactory())->create(timeGenerator: $generator);

        Uuid::setFactory($factory);

        $uuid = Uuid::uuid1();

        $this->assertSame('01234567-abcd-1432-9cba-0123456789ab', $uuid->toString());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUsingDefaultTimeGeneratorWithCustomProviders(): void
    {
        $nodeProvider = $this->createMock(NodeProviderInterface::class);
        $nodeProvider->method('getNode')->willReturn(new Hexadecimal('0123456789ab'));

        $timeConverter = $this->createMock(TimeConverterInterface::class);
        $timeConverter
            ->method('calculateTime')
            ->willReturnCallback(function (string $seconds, string $microseconds) {
                return new Hexadecimal('abcd' . dechex((int) $microseconds) . dechex((int) $seconds));
            });

        $timeProvider = $this->createMock(TimeProviderInterface::class);
        $timeProvider->method('getTime')->willReturn(new Time(1578522046, 10000));

        $generator = new DefaultTimeGenerator($nodeProvider, $timeConverter, $timeProvider);

        $factory = (new FastUuidFactoryFactory())->create(timeGenerator: $generator);

        Uuid::setFactory($factory);

        $uuid = Uuid::uuid1(null, 4095);

        $this->assertSame('5e1655be-2710-1bcd-8fff-0123456789ab', $uuid->toString());
    }
}
