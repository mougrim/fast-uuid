<?php
/** @noinspection PhpDeprecationInspection */
declare(strict_types=1);

namespace Mougrim\FastUuid\Test;

use BadMethodCallException;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use DateTimeInterface;
use Mougrim\FastUuid\Factory\FastUuidFactoryFactory;
use Mougrim\FastUuid\Fields\FastBytesFields;
use Mougrim\FastUuid\Fields\FastStringFields;
use Mougrim\FastUuid\Uuid\FastBytesUuid;
use Mougrim\FastUuid\Uuid\FastLazyUuidFromString;
use Mougrim\FastUuid\Uuid\FastStringUuid;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Builder\DefaultUuidBuilder;
use Ramsey\Uuid\Codec\StringCodec;
use Ramsey\Uuid\Codec\TimestampFirstCombCodec;
use Ramsey\Uuid\Codec\TimestampLastCombCodec;
use Ramsey\Uuid\Converter\Number\BigNumberConverter;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Exception\DateTimeException;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\Exception\InvalidUuidStringException;
use Ramsey\Uuid\Exception\UnsupportedOperationException;
use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\Generator\CombGenerator;
use Ramsey\Uuid\Generator\RandomGeneratorFactory;
use Ramsey\Uuid\Generator\RandomGeneratorInterface;
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Provider\Time\FixedTimeProvider;
use Ramsey\Uuid\Rfc4122\FieldsInterface;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Time;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;
use Ramsey\Uuid\Validator\ValidatorInterface;
use Stringable;
use stdClass;

use function base64_decode;
use function base64_encode;
use function gmdate;
use function hex2bin;
use function json_encode;
use function serialize;
use function str_pad;
use function strlen;
use function strtotime;
use function strtoupper;
use function substr;
use function uniqid;
use function unserialize;
use function usleep;
use const JSON_THROW_ON_ERROR;

/**
 * Based on https://github.com/ramsey/uuid/blob/4.7.6/tests/UuidTest.php
 */
class UuidTest extends TestCase
{
    protected function setUp(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory();
    }

    public function testFromString(): void
    {
        $this->assertSame(
            'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
            Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')
                ->toString()
        );
    }

    public function testLazyFromString(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $this->assertSame(
            'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
            Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->toString()
        );
    }

    public function testFromHexadecimal(): void
    {
        $hex = new Hexadecimal('0x1EA78DEB37CE625E8F1A025041000001');
        $uuid = Uuid::fromHexadecimal($hex);
        $this->assertEquals('1ea78deb-37ce-625e-8f1a-025041000001', $uuid->toString());
    }

    public function testLazyFromHexadecimal(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $hex = new Hexadecimal('0x1EA78DEB37CE625E8F1A025041000001');
        $uuid = Uuid::fromHexadecimal($hex);
        $this->assertEquals('1ea78deb-37ce-625e-8f1a-025041000001', $uuid->toString());
    }

    public function testFromHexadecimalShort(): void
    {
        $hex = new Hexadecimal('0x1EA78DEB37CE625E8F1A0250410000');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The byte string must be 16 bytes long; received 15 bytes');

        Uuid::fromHexadecimal($hex);
    }

    public function testLazyFromHexadecimalShort(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $hex = new Hexadecimal('0x1EA78DEB37CE625E8F1A0250410000');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID string: 1ea78deb-37ce-625e-8f1a-0250410000');

        Uuid::fromHexadecimal($hex);
    }

    public function testFromHexadecimalThrowsWhenMethodDoesNotExist(): void
    {
        $factory = $this->createMock(UuidFactoryInterface::class);
        Uuid::setFactory($factory);

        $hex = new Hexadecimal('0x1EA78DEB37CE625E8F1A025041000001');

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('The method fromHexadecimal() does not exist on the provided factory');

        Uuid::fromHexadecimal($hex);
    }

    public function testFromGuidString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Guid is not supported by FastUuid yet');
        (new FastUuidFactoryFactory())->create(new FeatureSet(true));
    }

    public function testFromStringWithCurlyBraces(): void
    {
        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string: {ff6f8cb0-c57d-11e1-9b21-0800200c9a66}');
        Uuid::fromString('{ff6f8cb0-c57d-11e1-9b21-0800200c9a66}');
    }

    public function testLazyFromStringWithCurlyBraces(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string: {ff6f8cb0-c57d-11e1-9b21-0800200c9a66}');
        Uuid::fromString('{ff6f8cb0-c57d-11e1-9b21-0800200c9a66}');
    }

    public function testFromStringWithInvalidUuidString(): void
    {
        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string:');

        Uuid::fromString('ff6f8cb0-c57d-11e1-9b21');
    }

    public function testLazyFromStringWithInvalidUuidString(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string:');

        Uuid::fromString('ff6f8cb0-c57d-11e1-9b21');
    }

    public function testFromStringWithLeadingNewLine(): void
    {
        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string:');

        Uuid::fromString("\nd0d5f586-21d1-470c-8088-55c8857728dc");
    }

    public function testLazyFromStringWithLeadingNewLine(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string:');

        Uuid::fromString("\nd0d5f586-21d1-470c-8088-55c8857728dc");
    }

    public function testFromStringWithTrailingNewLine(): void
    {
        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string:');

        Uuid::fromString("d0d5f586-21d1-470c-8088-55c8857728dc\n");
    }

    public function testLazyFromStringWithTrailingNewLine(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string:');

        Uuid::fromString("d0d5f586-21d1-470c-8088-55c8857728dc\n");
    }

    public function testFromStringWithUrn(): void
    {
        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string: urn:uuid:ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        Uuid::fromString('urn:uuid:ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
    }

    public function testLazyFromStringWithUrn(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string: urn:uuid:ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        Uuid::fromString('urn:uuid:ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
    }

    public function testFromStringWithEmptyString(): void
    {
        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string: ');

        Uuid::fromString('');
    }

    public function testLazyFromStringWithEmptyString(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $this->expectException(InvalidUuidStringException::class);
        $this->expectExceptionMessage('Invalid UUID string: ');

        Uuid::fromString('');
    }

    public function testFromStringUppercase(): void
    {
        $uuid = Uuid::fromString('FF6F8CB0-C57D-11E1-9B21-0800200C9A66');
        $this->assertSame('ff6f8cb0-c57d-11e1-9b21-0800200c9a66', $uuid->toString());
    }

    public function testFromStringUppercaseDirectlyUsingFactory(): void
    {
        $this->expectException(InvalidUuidStringException::class);
        Uuid::getFactory()->fromString('FF6F8CB0-C57D-11E1-9B21-0800200C9A66');
    }

    public function testFromStringWithNilUuid(): void
    {
        $uuid = Uuid::fromString(Uuid::NIL);

        /** @var FastStringFields $fields */
        $fields = $uuid->getFields();

        $this->assertSame('00000000-0000-0000-0000-000000000000', $uuid->toString());
        $this->assertTrue($fields->isNil());
        $this->assertFalse($fields->isMax());
    }

    public function testLazyFromStringWithNilUuid(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);
        $uuid = Uuid::fromString(Uuid::NIL);

        /** @var FastStringFields $fields */
        $fields = $uuid->getFields();

        $this->assertSame('00000000-0000-0000-0000-000000000000', $uuid->toString());
        $this->assertTrue($fields->isNil());
        $this->assertFalse($fields->isMax());
    }

    public function testFromBytesWithNilUuid(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString(Uuid::NIL)->getBytes());

        /** @var FastBytesFields $fields */
        $fields = $uuid->getFields();

        $this->assertSame('00000000-0000-0000-0000-000000000000', $uuid->toString());
        $this->assertTrue($fields->isNil());
        $this->assertFalse($fields->isMax());
    }

    public function testLazyFromBytesWithNilUuid(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);
        $uuid = Uuid::fromBytes(Uuid::fromString(Uuid::NIL)->getBytes());

        /** @var FastStringFields $fields */
        $fields = $uuid->getFields();

        $this->assertSame('00000000-0000-0000-0000-000000000000', $uuid->toString());
        $this->assertTrue($fields->isNil());
        $this->assertFalse($fields->isMax());
    }

    public function testFromStringWithMaxUuid(): void
    {
        $uuid = Uuid::fromString(Uuid::MAX);

        /** @var FastStringFields $fields */
        $fields = $uuid->getFields();

        $this->assertSame('ffffffff-ffff-ffff-ffff-ffffffffffff', $uuid->toString());
        $this->assertFalse($fields->isNil());
        $this->assertTrue($fields->isMax());
    }

    public function testLazyFromStringWithMaxUuid(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);
        $uuid = Uuid::fromString(Uuid::MAX);

        /** @var FastStringFields $fields */
        $fields = $uuid->getFields();

        $this->assertSame('ffffffff-ffff-ffff-ffff-ffffffffffff', $uuid->toString());
        $this->assertFalse($fields->isNil());
        $this->assertTrue($fields->isMax());
    }

    public function testFromBytesWithMaxUuid(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString(Uuid::MAX)->getBytes());

        /** @var FastBytesFields $fields */
        $fields = $uuid->getFields();

        $this->assertSame('ffffffff-ffff-ffff-ffff-ffffffffffff', $uuid->toString());
        $this->assertFalse($fields->isNil());
        $this->assertTrue($fields->isMax());
    }

    public function testLazyFromBytesWithMaxUuid(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);
        $uuid = Uuid::fromBytes(Uuid::fromString(Uuid::MAX)->getBytes());

        /** @var FastStringFields $fields */
        $fields = $uuid->getFields();

        $this->assertSame('ffffffff-ffff-ffff-ffff-ffffffffffff', $uuid->toString());
        $this->assertFalse($fields->isNil());
        $this->assertTrue($fields->isMax());
    }

    public function testStringUuidGetBytes(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame(16, strlen($uuid->getBytes()));
        $this->assertSame('/2+MsMV9EeGbIQgAIAyaZg==', base64_encode($uuid->getBytes()));
    }

    public function testLazyStringUuidGetBytes(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame(16, strlen($uuid->getBytes()));
        $this->assertSame('/2+MsMV9EeGbIQgAIAyaZg==', base64_encode($uuid->getBytes()));
    }

    public function testBytesUuidGetBytes(): void
    {
        $uuid = Uuid::fromBytes(base64_decode('/2+MsMV9EeGbIQgAIAyaZg=='));
        $this->assertSame(16, strlen($uuid->getBytes()));
        $this->assertSame('/2+MsMV9EeGbIQgAIAyaZg==', base64_encode($uuid->getBytes()));
    }

    public function testLazyBytesUuidGetBytes(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);
        $uuid = Uuid::fromBytes(base64_decode('/2+MsMV9EeGbIQgAIAyaZg=='));
        $this->assertSame(16, strlen($uuid->getBytes()));
        $this->assertSame('/2+MsMV9EeGbIQgAIAyaZg==', base64_encode($uuid->getBytes()));
    }

    public function testStringUuidGetClockSeqHiAndReserved(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('155', $uuid->getClockSeqHiAndReserved());
    }

    public function testBytesUuidGetClockSeqHiAndReserved(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('155', $uuid->getClockSeqHiAndReserved());
    }

    public function testStringUuidGetClockSeqHiAndReservedHex(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('9b', $uuid->getClockSeqHiAndReservedHex());
    }

    public function testLazyStringUuidGetClockSeqHiAndReservedHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        /** @var FastLazyUuidFromString $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('9b', $uuid->getClockSeqHiAndReservedHex());
    }

    public function testBytesUuidGetClockSeqHiAndReservedHex(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('9b', $uuid->getClockSeqHiAndReservedHex());
    }

    public function testStringUuidGetClockSeqLow(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('33', $uuid->getClockSeqLow());
    }

    public function testBytesUuidGetClockSeqLow(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('33', $uuid->getClockSeqLow());
    }

    public function testStringUuidGetClockSeqLowHex(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('21', $uuid->getClockSeqLowHex());
    }

    public function testLazyStringUuidGetClockSeqLowHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        /** @var FastLazyUuidFromString $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('21', $uuid->getClockSeqLowHex());
    }

    public function testBytesUuidGetClockSeqLowHex(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('21', $uuid->getClockSeqLowHex());
    }

    public function testStringUuidGetClockSequence(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('6945', $uuid->getClockSequence());
    }

    public function testBytesUuidGetClockSequence(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('6945', $uuid->getClockSequence());
    }

    public function testStringUuidGetClockSequenceHex(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('1b21', $uuid->getClockSequenceHex());
    }

    public function testLazyStringUuidGetClockSequenceHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        /** @var FastLazyUuidFromString $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('1b21', $uuid->getClockSequenceHex());
    }

    public function testBytesUuidGetClockSequenceHex(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('1b21', $uuid->getClockSequenceHex());
    }

    public function testStringUuidGetDateTime(): void
    {
        // Check a recent date
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('2012-07-04T02:14:34+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('1341368074.491000', $uuid->getDateTime()->format('U.u'));

        // Check an old date
        $uuid = Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66');
        $this->assertSame('1582-10-16T16:34:04+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219146756.000000', $uuid->getDateTime()->format('U.u'));

        // Check a future date
        $uuid = Uuid::fromString('ff9785f6-ffff-1fff-9669-00007ffffffe');
        $this->assertSame('5236-03-31T21:20:59+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857659.999999', $uuid->getDateTime()->format('U.u'));

        // Check the last possible time supported by v1 UUIDs
        // See inline comments in
        // {@see \Ramsey\Uuid\Test\Converter\Time\GenericTimeConverterTest::provideCalculateTime()}
        $uuid = Uuid::fromString('fffffffa-ffff-1fff-8b1e-acde48001122');
        $this->assertSame('5236-03-31T21:21:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857660.684697', $uuid->getDateTime()->format('U.u'));

        // Check the oldest date
        $uuid = Uuid::fromString('00000000-0000-1000-9669-00007ffffffe');
        $this->assertSame('1582-10-15T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219292800.000000', $uuid->getDateTime()->format('U.u'));

        // The Unix epoch
        $uuid = Uuid::fromString('13814000-1dd2-11b2-9669-00007ffffffe');
        $this->assertSame('1970-01-01T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('0.000000', $uuid->getDateTime()->format('U.u'));
    }

    public function testLazyStringUuidGetDateTime(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        // Check a recent date
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('2012-07-04T02:14:34+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('1341368074.491000', $uuid->getDateTime()->format('U.u'));

        // Check an old date
        $uuid = Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66');
        $this->assertSame('1582-10-16T16:34:04+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219146756.000000', $uuid->getDateTime()->format('U.u'));

        // Check a future date
        $uuid = Uuid::fromString('ff9785f6-ffff-1fff-9669-00007ffffffe');
        $this->assertSame('5236-03-31T21:20:59+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857659.999999', $uuid->getDateTime()->format('U.u'));

        // Check the last possible time supported by v1 UUIDs
        // See inline comments in
        // {@see \Ramsey\Uuid\Test\Converter\Time\GenericTimeConverterTest::provideCalculateTime()}
        $uuid = Uuid::fromString('fffffffa-ffff-1fff-8b1e-acde48001122');
        $this->assertSame('5236-03-31T21:21:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857660.684697', $uuid->getDateTime()->format('U.u'));

        // Check the oldest date
        $uuid = Uuid::fromString('00000000-0000-1000-9669-00007ffffffe');
        $this->assertSame('1582-10-15T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219292800.000000', $uuid->getDateTime()->format('U.u'));

        // The Unix epoch
        $uuid = Uuid::fromString('13814000-1dd2-11b2-9669-00007ffffffe');
        $this->assertSame('1970-01-01T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('0.000000', $uuid->getDateTime()->format('U.u'));
    }

    public function testBytesUuidGetDateTime(): void
    {
        // Check a recent date
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('2012-07-04T02:14:34+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('1341368074.491000', $uuid->getDateTime()->format('U.u'));

        // Check an old date
        $uuid = Uuid::fromBytes(Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66')->getBytes());
        $this->assertSame('1582-10-16T16:34:04+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219146756.000000', $uuid->getDateTime()->format('U.u'));

        // Check a future date
        $uuid = Uuid::fromBytes(Uuid::fromString('ff9785f6-ffff-1fff-9669-00007ffffffe')->getBytes());
        $this->assertSame('5236-03-31T21:20:59+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857659.999999', $uuid->getDateTime()->format('U.u'));

        // Check the last possible time supported by v1 UUIDs
        // See inline comments in
        // {@see \Ramsey\Uuid\Test\Converter\Time\GenericTimeConverterTest::provideCalculateTime()}
        $uuid = Uuid::fromBytes(Uuid::fromString('fffffffa-ffff-1fff-8b1e-acde48001122')->getBytes());
        $this->assertSame('5236-03-31T21:21:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857660.684697', $uuid->getDateTime()->format('U.u'));

        // Check the oldest date
        $uuid = Uuid::fromBytes(Uuid::fromString('00000000-0000-1000-9669-00007ffffffe')->getBytes());
        $this->assertSame('1582-10-15T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219292800.000000', $uuid->getDateTime()->format('U.u'));

        // The Unix epoch
        $uuid = Uuid::fromBytes(Uuid::fromString('13814000-1dd2-11b2-9669-00007ffffffe')->getBytes());
        $this->assertSame('1970-01-01T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('0.000000', $uuid->getDateTime()->format('U.u'));
    }

    public function testStringUuidGetDateTimeForUuidV6(): void
    {
        // Check a recent date
        $uuid = Uuid::fromString('1e1c57df-f6f8-6cb0-9b21-0800200c9a66');
        $this->assertSame('2012-07-04T02:14:34+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('1341368074.491000', $uuid->getDateTime()->format('U.u'));

        // Check an old date
        $uuid = Uuid::fromString('00001540-901e-6600-9b21-0800200c9a66');
        $this->assertSame('1582-10-16T16:34:04+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219146756.000000', $uuid->getDateTime()->format('U.u'));

        // Check a future date
        $uuid = Uuid::fromString('ffffffff-f978-65f6-9669-00007ffffffe');
        $this->assertSame('5236-03-31T21:20:59+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857659.999999', $uuid->getDateTime()->format('U.u'));

        // Check the last possible time supported by UUIDs
        // See inline comments in
        // {@see \Ramsey\Uuid\Test\Converter\Time\GenericTimeConverterTest::provideCalculateTime()}
        $uuid = Uuid::fromString('ffffffff-ffff-6ffa-8b1e-acde48001122');
        $this->assertSame('5236-03-31T21:21:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857660.684697', $uuid->getDateTime()->format('U.u'));

        // Check the oldest date
        $uuid = Uuid::fromString('00000000-0000-6000-9669-00007ffffffe');
        $this->assertSame('1582-10-15T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219292800.000000', $uuid->getDateTime()->format('U.u'));

        // The Unix epoch
        $uuid = Uuid::fromString('1b21dd21-3814-6000-9669-00007ffffffe');
        $this->assertSame('1970-01-01T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('0.000000', $uuid->getDateTime()->format('U.u'));
    }

    public function testLazyStringUuidGetDateTimeForUuidV6(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        // Check a recent date
        $uuid = Uuid::fromString('1e1c57df-f6f8-6cb0-9b21-0800200c9a66');
        $this->assertSame('2012-07-04T02:14:34+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('1341368074.491000', $uuid->getDateTime()->format('U.u'));

        // Check an old date
        $uuid = Uuid::fromString('00001540-901e-6600-9b21-0800200c9a66');
        $this->assertSame('1582-10-16T16:34:04+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219146756.000000', $uuid->getDateTime()->format('U.u'));

        // Check a future date
        $uuid = Uuid::fromString('ffffffff-f978-65f6-9669-00007ffffffe');
        $this->assertSame('5236-03-31T21:20:59+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857659.999999', $uuid->getDateTime()->format('U.u'));

        // Check the last possible time supported by UUIDs
        // See inline comments in
        // {@see \Ramsey\Uuid\Test\Converter\Time\GenericTimeConverterTest::provideCalculateTime()}
        $uuid = Uuid::fromString('ffffffff-ffff-6ffa-8b1e-acde48001122');
        $this->assertSame('5236-03-31T21:21:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857660.684697', $uuid->getDateTime()->format('U.u'));

        // Check the oldest date
        $uuid = Uuid::fromString('00000000-0000-6000-9669-00007ffffffe');
        $this->assertSame('1582-10-15T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219292800.000000', $uuid->getDateTime()->format('U.u'));

        // The Unix epoch
        $uuid = Uuid::fromString('1b21dd21-3814-6000-9669-00007ffffffe');
        $this->assertSame('1970-01-01T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('0.000000', $uuid->getDateTime()->format('U.u'));
    }

    public function testBytesUuidGetDateTimeForUuidV6(): void
    {
        // Check a recent date
        $uuid = Uuid::fromBytes(Uuid::fromString('1e1c57df-f6f8-6cb0-9b21-0800200c9a66')->getBytes());
        $this->assertSame('2012-07-04T02:14:34+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('1341368074.491000', $uuid->getDateTime()->format('U.u'));

        // Check an old date
        $uuid = Uuid::fromBytes(Uuid::fromString('00001540-901e-6600-9b21-0800200c9a66')->getBytes());
        $this->assertSame('1582-10-16T16:34:04+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219146756.000000', $uuid->getDateTime()->format('U.u'));

        // Check a future date
        $uuid = Uuid::fromBytes(Uuid::fromString('ffffffff-f978-65f6-9669-00007ffffffe')->getBytes());
        $this->assertSame('5236-03-31T21:20:59+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857659.999999', $uuid->getDateTime()->format('U.u'));

        // Check the last possible time supported by UUIDs
        // See inline comments in
        // {@see \Ramsey\Uuid\Test\Converter\Time\GenericTimeConverterTest::provideCalculateTime()}
        $uuid = Uuid::fromBytes(Uuid::fromString('ffffffff-ffff-6ffa-8b1e-acde48001122')->getBytes());
        $this->assertSame('5236-03-31T21:21:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('103072857660.684697', $uuid->getDateTime()->format('U.u'));

        // Check the oldest date
        $uuid = Uuid::fromBytes(Uuid::fromString('00000000-0000-6000-9669-00007ffffffe')->getBytes());
        $this->assertSame('1582-10-15T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('-12219292800.000000', $uuid->getDateTime()->format('U.u'));

        // The Unix epoch
        $uuid = Uuid::fromBytes(Uuid::fromString('1b21dd21-3814-6000-9669-00007ffffffe')->getBytes());
        $this->assertSame('1970-01-01T00:00:00+00:00', $uuid->getDateTime()->format('c'));
        $this->assertSame('0.000000', $uuid->getDateTime()->format('U.u'));
    }

    public function testStringUuidGetDateTimeFromNonVersion1Uuid(): void
    {
        // Using a version 4 UUID to test
        $uuid = Uuid::fromString('bf17b594-41f2-474f-bf70-4c90220f75de');

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Not a time-based UUID');

        $uuid->getDateTime();
    }

    public function testLazyStringUuidGetDateTimeFromNonVersion1Uuid(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        // Using a version 4 UUID to test
        $uuid = Uuid::fromString('bf17b594-41f2-474f-bf70-4c90220f75de');

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Not a time-based UUID');

        $uuid->getDateTime();
    }

    public function testBytesUuidGetDateTimeFromNonVersion1Uuid(): void
    {
        // Using a version 4 UUID to test
        $uuid = Uuid::fromBytes(Uuid::fromString('bf17b594-41f2-474f-bf70-4c90220f75de')->getBytes());

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Not a time-based UUID');

        $uuid->getDateTime();
    }

    public function testStringUuidGetFields(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        $this->assertInstanceOf(FieldsInterface::class, $uuid->getFields());
    }

    public function testLazyStringUuidGetFields(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        /** @var FastLazyUuidFromString $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        $this->assertInstanceOf(FieldsInterface::class, $uuid->getFields());
    }

    public function testBytesUuidGetFields(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());

        $this->assertInstanceOf(FieldsInterface::class, $uuid->getFields());
    }

    public function testStringUuidGetFieldsHex(): void
    {
        $fields = [
            'time_low' => 'ff6f8cb0',
            'time_mid' => 'c57d',
            'time_hi_and_version' => '11e1',
            'clock_seq_hi_and_reserved' => '9b',
            'clock_seq_low' => '21',
            'node' => '0800200c9a66',
        ];

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        $this->assertSame($fields, $uuid->getFieldsHex());
    }

    public function testLazyStringUuidGetFieldsHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $fields = [
            'time_low' => 'ff6f8cb0',
            'time_mid' => 'c57d',
            'time_hi_and_version' => '11e1',
            'clock_seq_hi_and_reserved' => '9b',
            'clock_seq_low' => '21',
            'node' => '0800200c9a66',
        ];

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        $this->assertSame($fields, $uuid->getFieldsHex());
    }

    public function testBytesUuidGetFieldsHex(): void
    {
        $fields = [
            'time_low' => 'ff6f8cb0',
            'time_mid' => 'c57d',
            'time_hi_and_version' => '11e1',
            'clock_seq_hi_and_reserved' => '9b',
            'clock_seq_low' => '21',
            'node' => '0800200c9a66',
        ];

        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());

        $this->assertSame($fields, $uuid->getFieldsHex());
    }

    public function testStringUuidGetLeastSignificantBits(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        $this->assertSame('11178224546741000806', $uuid->getLeastSignificantBits());
    }

    public function testBytesUuidGetLeastSignificantBits(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());

        $this->assertSame('11178224546741000806', $uuid->getLeastSignificantBits());
    }

    public function testStringUuidGetLeastSignificantBitsHex(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        $this->assertSame('9b210800200c9a66', $uuid->getLeastSignificantBitsHex());
    }

    public function testLazyStringUuidGetLeastSignificantBitsHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        $this->assertSame('9b210800200c9a66', $uuid->getLeastSignificantBitsHex());
    }

    public function testBytesUuidGetLeastSignificantBitsHex(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());

        $this->assertSame('9b210800200c9a66', $uuid->getLeastSignificantBitsHex());
    }

    public function testStringUuidGetMostSignificantBits(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        $this->assertSame('18406084892941947361', $uuid->getMostSignificantBits());
    }

    public function testBytesUuidGetMostSignificantBits(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());

        $this->assertSame('18406084892941947361', $uuid->getMostSignificantBits());
    }

    public function testStringUuidGetMostSignificantBitsHex(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('ff6f8cb0c57d11e1', $uuid->getMostSignificantBitsHex());
    }

    public function testLazyStringUuidGetMostSignificantBitsHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('ff6f8cb0c57d11e1', $uuid->getMostSignificantBitsHex());
    }

    public function testBytesUuidGetMostSignificantBitsHex(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('ff6f8cb0c57d11e1', $uuid->getMostSignificantBitsHex());
    }

    public function testStringUuidGetNode(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('8796630719078', $uuid->getNode());
    }

    public function testBytesUuidGetNode(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('8796630719078', $uuid->getNode());
    }

    public function testStringUuidGetNodeHex(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('0800200c9a66', $uuid->getNodeHex());
    }

    public function testLazyStringUuidGetNodeHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('0800200c9a66', $uuid->getNodeHex());
    }

    public function testBytesUuidGetNodeHex(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('0800200c9a66', $uuid->getNodeHex());
    }

    public function testStringUuidGetTimeHiAndVersion(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('4577', $uuid->getTimeHiAndVersion());
    }

    public function testBytesUuidGetTimeHiAndVersion(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('4577', $uuid->getTimeHiAndVersion());
    }

    public function testStringUuidGetTimeHiAndVersionHex(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('11e1', $uuid->getTimeHiAndVersionHex());
    }

    public function testLazyStringUuidGetTimeHiAndVersionHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('11e1', $uuid->getTimeHiAndVersionHex());
    }

    public function testBytesUuidGetTimeHiAndVersionHex(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('11e1', $uuid->getTimeHiAndVersionHex());
    }

    public function testStringUuidGetTimeLow(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('4285500592', $uuid->getTimeLow());
    }

    public function testBytesUuidGetTimeLow(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('4285500592', $uuid->getTimeLow());
    }

    public function testStringUuidGetTimeLowHex(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('ff6f8cb0', $uuid->getTimeLowHex());
    }

    public function testLazyStringUuidGetTimeLowHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('ff6f8cb0', $uuid->getTimeLowHex());
    }

    public function testBytesUuidGetTimeLowHex(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('ff6f8cb0', $uuid->getTimeLowHex());
    }

    public function testStringUuidGetTimeMid(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('50557', $uuid->getTimeMid());
    }

    public function testBytesUuidGetTimeMid(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('50557', $uuid->getTimeMid());
    }

    public function testStringUuidGetTimeMidHex(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('c57d', $uuid->getTimeMidHex());
    }

    public function testLazyStringUuidGetTimeMidHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('c57d', $uuid->getTimeMidHex());
    }

    public function testBytesUuidGetTimeMidHex(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('c57d', $uuid->getTimeMidHex());
    }

    public function testStringUuidGetTimestamp(): void
    {
        // Check for a recent date
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('135606608744910000', $uuid->getTimestamp());

        // Check for an old date
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66');
        $this->assertSame('1460440000000', $uuid->getTimestamp());
    }

    public function testBytesUuidGetTimestamp(): void
    {
        // Check for a recent date
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('135606608744910000', $uuid->getTimestamp());

        // Check for an old date
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66')->getBytes());
        $this->assertSame('1460440000000', $uuid->getTimestamp());
    }

    public function testStringUuidGetTimestampHex(): void
    {
        // Check for a recent date
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('1e1c57dff6f8cb0', $uuid->getTimestampHex());

        // Check for an old date
        $uuid = Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66');
        $this->assertSame('00001540901e600', $uuid->getTimestampHex());
    }

    public function testLazyStringUuidGetTimestampHex(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        // Check for a recent date
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('1e1c57dff6f8cb0', $uuid->getTimestampHex());

        // Check for an old date
        $uuid = Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66');
        $this->assertSame('00001540901e600', $uuid->getTimestampHex());
    }

    public function testBytesUuidGetTimestampHex(): void
    {
        // Check for a recent date
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('1e1c57dff6f8cb0', $uuid->getTimestampHex());

        // Check for an old date
        $uuid = Uuid::fromBytes(Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66')->getBytes());
        $this->assertSame('00001540901e600', $uuid->getTimestampHex());
    }

    public function testStringUuidGetTimestampFromNonVersion1Uuid(): void
    {
        // Using a version 4 UUID to test
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('bf17b594-41f2-474f-bf70-4c90220f75de');

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Not a time-based UUID');

        $uuid->getTimestamp();
    }

    public function testBytesUuidGetTimestampFromNonVersion1Uuid(): void
    {
        // Using a version 4 UUID to test
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::fromBytes(Uuid::fromString('bf17b594-41f2-474f-bf70-4c90220f75de')->getBytes());

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Not a time-based UUID');

        $uuid->getTimestamp();
    }

    public function testStringUuidGetTimestampHexFromNonVersion1Uuid(): void
    {
        // Using a version 4 UUID to test
        $uuid = Uuid::fromString('bf17b594-41f2-474f-bf70-4c90220f75de');

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Not a time-based UUID');

        $uuid->getTimestampHex();
    }

    public function testLazyStringUuidGetTimestampHexFromNonVersion1Uuid(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        // Using a version 4 UUID to test
        $uuid = Uuid::fromString('bf17b594-41f2-474f-bf70-4c90220f75de');

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Not a time-based UUID');

        $uuid->getTimestampHex();
    }

    public function testBytesUuidGetTimestampHexFromNonVersion1Uuid(): void
    {
        // Using a version 4 UUID to test
        $uuid = Uuid::fromBytes(Uuid::fromString('bf17b594-41f2-474f-bf70-4c90220f75de')->getBytes());

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('Not a time-based UUID');

        $uuid->getTimestampHex();
    }

    public function testStringUuidGetUrn(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('urn:uuid:ff6f8cb0-c57d-11e1-9b21-0800200c9a66', $uuid->getUrn());
    }

    public function testLazyStringUuidGetUrn(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('urn:uuid:ff6f8cb0-c57d-11e1-9b21-0800200c9a66', $uuid->getUrn());
    }

    public function testBytesUuidGetUrn(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('urn:uuid:ff6f8cb0-c57d-11e1-9b21-0800200c9a66', $uuid->getUrn());
    }

    /**
     * @param non-empty-string $uuid
     */
    #[DataProvider('provideVariousVariantUuids')]
    public function testStringUuidGetVariantForVariousVariantUuids(string $uuid, int $variant): void
    {
        $uuid = Uuid::fromString($uuid);
        $this->assertSame($variant, $uuid->getVariant());
    }

    /**
     * @param non-empty-string $uuid
     */
    #[DataProvider('provideVariousVariantUuids')]
    public function testLazyStringUuidGetVariantForVariousVariantUuids(string $uuid, int $variant): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString($uuid);
        $this->assertSame($variant, $uuid->getVariant());
    }

    /**
     * @param non-empty-string $uuid
     */
    #[DataProvider('provideVariousVariantUuids')]
    public function testBytesUuidGetVariantForVariousVariantUuids(string $uuid, int $variant): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString($uuid)->getBytes());
        $this->assertSame($variant, $uuid->getVariant());
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function provideVariousVariantUuids(): array
    {
        return [
            ['ff6f8cb0-c57d-11e1-0b21-0800200c9a66', Uuid::RESERVED_NCS],
            ['ff6f8cb0-c57d-11e1-1b21-0800200c9a66', Uuid::RESERVED_NCS],
            ['ff6f8cb0-c57d-11e1-2b21-0800200c9a66', Uuid::RESERVED_NCS],
            ['ff6f8cb0-c57d-11e1-3b21-0800200c9a66', Uuid::RESERVED_NCS],
            ['ff6f8cb0-c57d-11e1-4b21-0800200c9a66', Uuid::RESERVED_NCS],
            ['ff6f8cb0-c57d-11e1-5b21-0800200c9a66', Uuid::RESERVED_NCS],
            ['ff6f8cb0-c57d-11e1-6b21-0800200c9a66', Uuid::RESERVED_NCS],
            ['ff6f8cb0-c57d-11e1-7b21-0800200c9a66', Uuid::RESERVED_NCS],
            ['ff6f8cb0-c57d-11e1-8b21-0800200c9a66', Uuid::RFC_4122],
            ['ff6f8cb0-c57d-11e1-9b21-0800200c9a66', Uuid::RFC_4122],
            ['ff6f8cb0-c57d-11e1-ab21-0800200c9a66', Uuid::RFC_4122],
            ['ff6f8cb0-c57d-11e1-bb21-0800200c9a66', Uuid::RFC_4122],
            ['ff6f8cb0-c57d-11e1-cb21-0800200c9a66', Uuid::RESERVED_MICROSOFT],
            ['ff6f8cb0-c57d-11e1-db21-0800200c9a66', Uuid::RESERVED_MICROSOFT],
            ['ff6f8cb0-c57d-11e1-eb21-0800200c9a66', Uuid::RESERVED_FUTURE],
            ['ff6f8cb0-c57d-11e1-fb21-0800200c9a66', Uuid::RESERVED_FUTURE],
        ];
    }

    public function testStringUuidGetVersionVersion1(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testLazyStringUuidGetVersionVersion1(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testBytesUuidGetVersionVersion1(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testStringUuidGetVersionVersion2(): void
    {
        $uuid = Uuid::fromString('6fa459ea-ee8a-2ca4-894e-db77e160355e');
        $this->assertSame(Uuid::UUID_TYPE_DCE_SECURITY, $uuid->getVersion());
    }

    public function testLazyStringUuidGetVersionVersion2(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('6fa459ea-ee8a-2ca4-894e-db77e160355e');
        $this->assertSame(Uuid::UUID_TYPE_DCE_SECURITY, $uuid->getVersion());
    }

    public function testBytesUuidGetVersionVersion2(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('6fa459ea-ee8a-2ca4-894e-db77e160355e')->getBytes());
        $this->assertSame(Uuid::UUID_TYPE_DCE_SECURITY, $uuid->getVersion());
    }

    public function testStringUuidGetVersionVersion3(): void
    {
        $uuid = Uuid::fromString('6fa459ea-ee8a-3ca4-894e-db77e160355e');
        $this->assertSame(Uuid::UUID_TYPE_HASH_MD5, $uuid->getVersion());
    }

    public function testLazyStringUuidGetVersionVersion3(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('6fa459ea-ee8a-3ca4-894e-db77e160355e');
        $this->assertSame(Uuid::UUID_TYPE_HASH_MD5, $uuid->getVersion());
    }

    public function testBytesUuidGetVersionVersion3(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('6fa459ea-ee8a-3ca4-894e-db77e160355e')->getBytes());
        $this->assertSame(Uuid::UUID_TYPE_HASH_MD5, $uuid->getVersion());
    }

    public function testStringUuidGetVersionVersion4(): void
    {
        $uuid = Uuid::fromString('6fabf0bc-603a-42f2-925b-d9f779bd0032');
        $this->assertSame(Uuid::UUID_TYPE_RANDOM, $uuid->getVersion());
    }

    public function testLazyStringUuidGetVersionVersion4(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('6fabf0bc-603a-42f2-925b-d9f779bd0032');
        $this->assertSame(Uuid::UUID_TYPE_RANDOM, $uuid->getVersion());
    }

    public function testBytesUuidGetVersionVersion4(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('6fabf0bc-603a-42f2-925b-d9f779bd0032')->getBytes());
        $this->assertSame(Uuid::UUID_TYPE_RANDOM, $uuid->getVersion());
    }

    public function testStringUuidGetVersionVersion5(): void
    {
        $uuid = Uuid::fromString('886313e1-3b8a-5372-9b90-0c9aee199e5d');
        $this->assertSame(Uuid::UUID_TYPE_HASH_SHA1, $uuid->getVersion());
    }

    public function testLazyStringUuidGetVersionVersion5(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('886313e1-3b8a-5372-9b90-0c9aee199e5d');
        $this->assertSame(Uuid::UUID_TYPE_HASH_SHA1, $uuid->getVersion());
    }

    public function testBytesUuidGetVersionVersion5(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('886313e1-3b8a-5372-9b90-0c9aee199e5d')->getBytes());
        $this->assertSame(Uuid::UUID_TYPE_HASH_SHA1, $uuid->getVersion());
    }

    public function testStringUuidGetVersionVersion6(): void
    {
        $uuid = Uuid::fromString('1ef35057-3ea2-67ea-9260-02427dba7ef2');
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testLazyStringUuidGetVersionVersion6(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('1ef35057-3ea2-67ea-9260-02427dba7ef2');
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testBytesUuidGetVersionVersion6(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('1ef35057-3ea2-67ea-9260-02427dba7ef2')->getBytes());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testStringUuidGetVersionVersion7(): void
    {
        $uuid = Uuid::fromString('01905d11-7136-73cb-919f-0577772e59fc');
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
    }

    public function testLazyStringUuidGetVersionVersion7(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('01905d11-7136-73cb-919f-0577772e59fc');
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
    }

    public function testBytesUuidGetVersionVersion7(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('01905d11-7136-73cb-919f-0577772e59fc')->getBytes());
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
    }

    public function testStringUuidGetVersionVersion8(): void
    {
        $uuid = Uuid::fromString('00112233-4455-8677-8899-aabbccddeeff');
        $this->assertSame(Uuid::UUID_TYPE_CUSTOM, $uuid->getVersion());
    }

    public function testLazyStringUuidGetVersionVersion8(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('00112233-4455-8677-8899-aabbccddeeff');
        $this->assertSame(Uuid::UUID_TYPE_CUSTOM, $uuid->getVersion());
    }

    public function testBytesUuidGetVersionVersion8(): void
    {
        $uuid = Uuid::fromBytes(Uuid::fromString('00112233-4455-8677-8899-aabbccddeeff')->getBytes());
        $this->assertSame(Uuid::UUID_TYPE_CUSTOM, $uuid->getVersion());
    }

    public function testStringUuidToString(): void
    {
        // Check with a recent date
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('ff6f8cb0-c57d-11e1-9b21-0800200c9a66', $uuid->toString());
        $this->assertSame('ff6f8cb0-c57d-11e1-9b21-0800200c9a66', (string) $uuid);
        $this->assertSame(
            'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
            (static fn (Stringable $uuid) => (string) $uuid)($uuid),
        );

        // Check with an old date
        $uuid = Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66');
        $this->assertSame('0901e600-0154-1000-9b21-0800200c9a66', $uuid->toString());
        $this->assertSame('0901e600-0154-1000-9b21-0800200c9a66', (string) $uuid);
        $this->assertSame(
            '0901e600-0154-1000-9b21-0800200c9a66',
            (static fn (Stringable $uuid) => (string) $uuid)($uuid),
        );
    }

    public function testLazyStringUuidToString(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        // Check with a recent date
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $this->assertSame('ff6f8cb0-c57d-11e1-9b21-0800200c9a66', $uuid->toString());
        $this->assertSame('ff6f8cb0-c57d-11e1-9b21-0800200c9a66', (string) $uuid);
        $this->assertSame(
            'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
            (static fn (Stringable $uuid) => (string) $uuid)($uuid),
        );

        // Check with an old date
        $uuid = Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66');
        $this->assertSame('0901e600-0154-1000-9b21-0800200c9a66', $uuid->toString());
        $this->assertSame('0901e600-0154-1000-9b21-0800200c9a66', (string) $uuid);
        $this->assertSame(
            '0901e600-0154-1000-9b21-0800200c9a66',
            (static fn (Stringable $uuid) => (string) $uuid)($uuid),
        );
    }

    public function testBytesUuidToString(): void
    {
        // Check with a recent date
        $uuid = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $this->assertSame('ff6f8cb0-c57d-11e1-9b21-0800200c9a66', $uuid->toString());
        $this->assertSame('ff6f8cb0-c57d-11e1-9b21-0800200c9a66', (string) $uuid);
        $this->assertSame(
            'ff6f8cb0-c57d-11e1-9b21-0800200c9a66',
            (static fn (Stringable $uuid) => (string) $uuid)($uuid)
        );

        // Check with an old date
        $uuid = Uuid::fromString('0901e600-0154-1000-9b21-0800200c9a66');
        $this->assertSame('0901e600-0154-1000-9b21-0800200c9a66', $uuid->toString());
        $this->assertSame('0901e600-0154-1000-9b21-0800200c9a66', (string) $uuid);
        $this->assertSame(
            '0901e600-0154-1000-9b21-0800200c9a66',
            (static fn (Stringable $uuid) => (string) $uuid)($uuid)
        );
    }

    public function testStringUuidUuid1(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid1()->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testLazyStringUuidUuid1(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid1()->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testBytesUuidUuid1(): void
    {
        $uuid = Uuid::uuid1();
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testStringUuidUuid1WithNodeAndClockSequence(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString(Uuid::uuid1('0800200c9a66', 0x1669)->toString());
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('5737', $uuid->getClockSequence());
        $this->assertSame('1669', $uuid->getClockSequenceHex());
        $this->assertSame('8796630719078', $uuid->getNode());
        $this->assertSame('0800200c9a66', $uuid->getNodeHex());
        $this->assertSame('9669-0800200c9a66', substr($uuid->toString(), 19));
    }

    public function testLazyStringUuidUuid1WithNodeAndClockSequence(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        /** @var FastLazyUuidFromString $uuid */
        $uuid = Uuid::fromString(Uuid::uuid1('0800200c9a66', 0x1669)->toString());
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('1669', $uuid->getClockSequenceHex());
        $this->assertSame('0800200c9a66', $uuid->getNodeHex());
        $this->assertSame('9669-0800200c9a66', substr($uuid->toString(), 19));
    }

    public function testBytesUuidUuid1WithNodeAndClockSequence(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::uuid1('0800200c9a66', 0x1669);
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('5737', $uuid->getClockSequence());
        $this->assertSame('1669', $uuid->getClockSequenceHex());
        $this->assertSame('8796630719078', $uuid->getNode());
        $this->assertSame('9669-0800200c9a66', substr($uuid->toString(), 19));
    }

    public function testStringUuidUuid1WithHexadecimalObjectNodeAndClockSequence(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString(Uuid::uuid1(new Hexadecimal('0800200c9a66'), 0x1669)->toString());
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('5737', $uuid->getClockSequence());
        $this->assertSame('1669', $uuid->getClockSequenceHex());
        $this->assertSame('8796630719078', $uuid->getNode());
        $this->assertSame('0800200c9a66', $uuid->getNodeHex());
        $this->assertSame('9669-0800200c9a66', substr($uuid->toString(), 19));
    }

    public function testLazyStringUuidUuid1WithHexadecimalObjectNodeAndClockSequence(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        /** @var FastLazyUuidFromString $uuid */
        $uuid = Uuid::fromString(Uuid::uuid1(new Hexadecimal('0800200c9a66'), 0x1669)->toString());
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('1669', $uuid->getClockSequenceHex());
        $this->assertSame('0800200c9a66', $uuid->getNodeHex());
        $this->assertSame('9669-0800200c9a66', substr($uuid->toString(), 19));
    }

    public function testBytesUuidUuid1WithHexadecimalObjectNodeAndClockSequence(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::uuid1(new Hexadecimal('0800200c9a66'), 0x1669);
        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('5737', $uuid->getClockSequence());
        $this->assertSame('8796630719078', $uuid->getNode());
        $this->assertSame('9669-0800200c9a66', substr($uuid->toString(), 19));
    }

    public function testStringUuidUuid1WithHexadecimalNode(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString(Uuid::uuid1('7160355e')->toString());

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('00007160355e', $uuid->getNodeHex());
        $this->assertSame('1902130526', $uuid->getNode());
    }

    public function testLazyStringUuidUuid1WithHexadecimalNode(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        /** @var FastLazyUuidFromString $uuid */
        $uuid = Uuid::fromString(Uuid::uuid1('7160355e')->toString());

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('00007160355e', $uuid->getNodeHex());
    }

    public function testBytesUuidUuid1WithHexadecimalNode(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::uuid1('7160355e');

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('00007160355e', $uuid->getNodeHex());
        $this->assertSame('1902130526', $uuid->getNode());
    }

    public function testStringUuidUuid1WithHexadecimalObjectNode(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString(Uuid::uuid1(new Hexadecimal('7160355e'))->toString());

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('00007160355e', $uuid->getNodeHex());
        $this->assertSame('1902130526', $uuid->getNode());
    }

    public function testLazyStringUuidUuid1WithHexadecimalObjectNode(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        /** @var FastLazyUuidFromString $uuid */
        $uuid = Uuid::fromString(Uuid::uuid1(new Hexadecimal('7160355e'))->toString());

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('00007160355e', $uuid->getNodeHex());
    }

    public function testBytesUuidUuid1WithHexadecimalObjectNode(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::uuid1(new Hexadecimal('7160355e'));

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('00007160355e', $uuid->getNodeHex());
        $this->assertSame('1902130526', $uuid->getNode());
    }

    public function testStringUuidUuid1WithMixedCaseHexadecimalNode(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString(Uuid::uuid1('71B0aD5e')->toString());

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('000071b0ad5e', $uuid->getNodeHex());
        $this->assertSame('1907404126', $uuid->getNode());
    }

    public function testLazyStringUuidUuid1WithMixedCaseHexadecimalNode(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        /** @var FastLazyUuidFromString $uuid */
        $uuid = Uuid::fromString(Uuid::uuid1('71B0aD5e')->toString());

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('000071b0ad5e', $uuid->getNodeHex());
    }

    public function testBytesUuidUuid1WithMixedCaseHexadecimalNode(): void
    {
        /** @var FastBytesUuid $uuid */
        $uuid = Uuid::uuid1('71B0aD5e');

        /** @noinspection UnnecessaryAssertionInspection */
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
        $this->assertSame('000071b0ad5e', $uuid->getNodeHex());
        $this->assertSame('1907404126', $uuid->getNode());
    }

    public function testUuid1WithOutOfBoundsNode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid node value');

        Uuid::uuid1('9223372036854775808');
    }

    public function testUuid1WithNonHexadecimalNode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid node value');

        Uuid::uuid1('db77e160355g');
    }

    public function testUuid1WithNon48bitNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid node value');

        Uuid::uuid1('db77e160355ef');
    }

    public function testStringUuidUuid1WithRandomNode(): void
    {
        Uuid::setFactory((new FastUuidFactoryFactory())->create(new FeatureSet(ignoreSystemNode: true)));

        $uuid = Uuid::fromString(Uuid::uuid1()->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testLazyStringUuidUuid1WithRandomNode(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(new FeatureSet(ignoreSystemNode: true), useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid1()->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testBytesUuidUuid1WithRandomNode(): void
    {
        Uuid::setFactory((new FastUuidFactoryFactory())->create(new FeatureSet(ignoreSystemNode: true)));

        $uuid = Uuid::uuid1();
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testStringUuidUuid1WithUserGeneratedRandomNode(): void
    {
        $uuid = Uuid::fromString(
            Uuid::uuid1(new Hexadecimal((string) (new RandomNodeProvider())->getNode()))->toString(),
        );
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testLazyStringUuidUuid1WithUserGeneratedRandomNode(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(
            Uuid::uuid1(new Hexadecimal((string) (new RandomNodeProvider())->getNode()))->toString(),
        );
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testBytesUuidUuid1WithUserGeneratedRandomNode(): void
    {
        $uuid = Uuid::uuid1(new Hexadecimal((string) (new RandomNodeProvider())->getNode()));
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_TIME, $uuid->getVersion());
    }

    public function testStringUuidUuid6(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid6()->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testLazyStringUuidUuid6(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid6()->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testBytesUuidUuid6(): void
    {
        $uuid = Uuid::uuid6();
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testStringUuidUuid6WithNodeAndClockSequence(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid6(new Hexadecimal('0800200c9a66'), 0x1669)->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
        $this->assertSame('1669', $uuid->getClockSequenceHex());
        $this->assertSame('0800200c9a66', $uuid->getNodeHex());
        $this->assertSame('9669-0800200c9a66', substr($uuid->toString(), 19));
    }

    public function testLazyStringUuidUuid6WithNodeAndClockSequence(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid6(new Hexadecimal('0800200c9a66'), 0x1669)->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
        $this->assertSame('1669', $uuid->getClockSequenceHex());
        $this->assertSame('0800200c9a66', $uuid->getNodeHex());
        $this->assertSame('9669-0800200c9a66', substr($uuid->toString(), 19));
    }

    public function testBytesUuidUuid6WithNodeAndClockSequence(): void
    {
        $uuid = Uuid::uuid6(new Hexadecimal('0800200c9a66'), 0x1669);
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
        $this->assertSame('1669', $uuid->getClockSequenceHex());
        $this->assertSame('0800200c9a66', $uuid->getNodeHex());
        $this->assertSame('9669-0800200c9a66', substr($uuid->toString(), 19));
    }

    public function testStringUuidUuid6WithHexadecimalNode(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid6(new Hexadecimal('7160355e'))->toString());

        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
        $this->assertSame('00007160355e', $uuid->getNodeHex());
    }

    public function testLazyStringUuidUuid6WithHexadecimalNode(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid6(new Hexadecimal('7160355e'))->toString());

        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
        $this->assertSame('00007160355e', $uuid->getNodeHex());
    }

    public function testBytesUuidUuid6WithHexadecimalNode(): void
    {
        $uuid = Uuid::uuid6(new Hexadecimal('7160355e'));

        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
        $this->assertSame('00007160355e', $uuid->getNodeHex());
    }

    public function testStringUuidUuid6WithMixedCaseHexadecimalNode(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid6(new Hexadecimal('71B0aD5e'))->toString());

        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
        $this->assertSame('000071b0ad5e', $uuid->getNodeHex());
    }

    public function testLazyStringUuidUuid6WithMixedCaseHexadecimalNode(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid6(new Hexadecimal('71B0aD5e'))->toString());

        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
        $this->assertSame('000071b0ad5e', $uuid->getNodeHex());
    }

    public function testBytesUuidUuid6WithMixedCaseHexadecimalNode(): void
    {
        $uuid = Uuid::uuid6(new Hexadecimal('71B0aD5e'));

        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
        $this->assertSame('000071b0ad5e', $uuid->getNodeHex());
    }

    public function testUuid6WithOutOfBoundsNode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid node value');

        Uuid::uuid6(new Hexadecimal('9223372036854775808'));
    }

    public function testUuid6WithNon48bitNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid node value');

        Uuid::uuid6(new Hexadecimal('db77e160355ef'));
    }

    public function testStringUuidUuid6WithRandomNode(): void
    {
        Uuid::setFactory((new FastUuidFactoryFactory())->create(new FeatureSet(ignoreSystemNode: true)));

        $uuid = Uuid::fromString(Uuid::uuid6()->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testLazyStringUuidUuid6WithRandomNode(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(new FeatureSet(ignoreSystemNode: true), useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid6()->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testBytesUuidUuid6WithRandomNode(): void
    {
        Uuid::setFactory((new FastUuidFactoryFactory())->create(new FeatureSet(ignoreSystemNode: true)));

        $uuid = Uuid::uuid6();
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testStringUuidUuid6WithUserGeneratedRandomNode(): void
    {
        $uuid = Uuid::fromString(
            Uuid::uuid6(new Hexadecimal((string) (new RandomNodeProvider())->getNode()))->toString(),
        );
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testLazyStringUuidUuid6WithUserGeneratedRandomNode(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(
            Uuid::uuid6(new Hexadecimal((string) (new RandomNodeProvider())->getNode()))->toString(),
        );
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testBytesUuidUuid6WithUserGeneratedRandomNode(): void
    {
        $uuid = Uuid::uuid6(new Hexadecimal((string) (new RandomNodeProvider())->getNode()));
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_REORDERED_TIME, $uuid->getVersion());
    }

    public function testStringUuidUuid7(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid7()->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
    }

    public function testLazyStringUuidUuid7(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid7()->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
    }

    public function testBytesUuidUuid7(): void
    {
        $uuid = Uuid::uuid7();
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
    }

    public function testUuid7ThrowsExceptionForUnsupportedFactory(): void
    {
        $factory = $this->createMock(UuidFactoryInterface::class);

        Uuid::setFactory($factory);

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('The provided factory does not support the uuid7() method');

        Uuid::uuid7();
    }

    public function testStringUuidUuid7WithDateTime(): void
    {
        $dateTime = new DateTimeImmutable('@281474976710.655');

        $uuid = Uuid::fromString(Uuid::uuid7($dateTime)->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
        $this->assertSame(
            '10889-08-02T05:31:50.655+00:00',
            $uuid->getDateTime()->format(DateTimeInterface::RFC3339_EXTENDED),
        );
    }

    public function testLazyStringUuidUuid7WithDateTime(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $dateTime = new DateTimeImmutable('@281474976710.655');

        $uuid = Uuid::fromString(Uuid::uuid7($dateTime)->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
        $this->assertSame(
            '10889-08-02T05:31:50.655+00:00',
            $uuid->getDateTime()->format(DateTimeInterface::RFC3339_EXTENDED),
        );
    }

    public function testBytesUuidUuid7WithDateTime(): void
    {
        $dateTime = new DateTimeImmutable('@281474976710.655');

        $uuid = Uuid::uuid7($dateTime);
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
        $this->assertSame(
            '10889-08-02T05:31:50.655+00:00',
            $uuid->getDateTime()->format(DateTimeInterface::RFC3339_EXTENDED),
        );
    }

    public function testStringUuidUuid7SettingTheClockBackwards(): void
    {
        $dates = [
            new DateTimeImmutable('now'),
            new DateTimeImmutable('last year'),
            new DateTimeImmutable('1979-01-01 00:00:00.000000'),
        ];

        foreach ($dates as $dateTime) {
            $previous = Uuid::fromString(Uuid::uuid7($dateTime)->toString());
            for ($i = 0; $i < 25; $i++) {
                $uuid = Uuid::fromString(Uuid::uuid7($dateTime)->toString());
                $this->assertGreaterThan(0, $uuid->compareTo($previous));
                $this->assertSame($dateTime->format('Y-m-d H:i'), $uuid->getDateTime()->format('Y-m-d H:i'));
                $previous = $uuid;
            }
        }
    }

    public function testLazyStringUuidUuid7SettingTheClockBackwards(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $dates = [
            new DateTimeImmutable('now'),
            new DateTimeImmutable('last year'),
            new DateTimeImmutable('1979-01-01 00:00:00.000000'),
        ];

        foreach ($dates as $dateTime) {
            $previous = Uuid::fromString(Uuid::uuid7($dateTime)->toString());
            for ($i = 0; $i < 25; $i++) {
                $uuid = Uuid::fromString(Uuid::uuid7($dateTime)->toString());
                $this->assertGreaterThan(0, $uuid->compareTo($previous));
                $this->assertSame($dateTime->format('Y-m-d H:i'), $uuid->getDateTime()->format('Y-m-d H:i'));
                $previous = $uuid;
            }
        }
    }

    public function testBytesUuidUuid7SettingTheClockBackwards(): void
    {
        $dates = [
            new DateTimeImmutable('now'),
            new DateTimeImmutable('last year'),
            new DateTimeImmutable('1979-01-01 00:00:00.000000'),
        ];

        foreach ($dates as $dateTime) {
            $previous = Uuid::uuid7($dateTime);
            for ($i = 0; $i < 25; $i++) {
                $uuid = Uuid::uuid7($dateTime);
                $this->assertGreaterThan(0, $uuid->compareTo($previous));
                $this->assertSame($dateTime->format('Y-m-d H:i'), $uuid->getDateTime()->format('Y-m-d H:i'));
                $previous = $uuid;
            }
        }
    }

    public function testStringUuidUuid7WithMinimumDateTime(): void
    {
        $dateTime = new DateTimeImmutable('1979-01-01 00:00:00.000000');

        $uuid = Uuid::fromString(Uuid::uuid7($dateTime)->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
        $this->assertSame(
            '1979-01-01T00:00:00.000+00:00',
            $uuid->getDateTime()->format(DateTimeInterface::RFC3339_EXTENDED),
        );
    }

    public function testLazyStringUuidUuid7WithMinimumDateTime(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $dateTime = new DateTimeImmutable('1979-01-01 00:00:00.000000');

        $uuid = Uuid::fromString(Uuid::uuid7($dateTime)->toString());
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
        $this->assertSame(
            '1979-01-01T00:00:00.000+00:00',
            $uuid->getDateTime()->format(DateTimeInterface::RFC3339_EXTENDED),
        );
    }

    public function testBytesUuidUuid7WithMinimumDateTime(): void
    {
        $dateTime = new DateTimeImmutable('1979-01-01 00:00:00.000000');

        $uuid = Uuid::uuid7($dateTime);
        $this->assertInstanceOf(DateTimeInterface::class, $uuid->getDateTime());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_UNIX_TIME, $uuid->getVersion());
        $this->assertSame(
            '1979-01-01T00:00:00.000+00:00',
            $uuid->getDateTime()->format(DateTimeInterface::RFC3339_EXTENDED),
        );
    }

    public function testStringUuidUuid7EachUuidIsMonotonicallyIncreasing(): void
    {
        $previous = Uuid::uuid7();

        for ($i = 0; $i < 25; $i++) {
            $uuid = Uuid::fromString(Uuid::uuid7()->toString());
            $now = gmdate('Y-m-d H:i');
            $this->assertGreaterThan(0, $uuid->compareTo($previous));
            $this->assertSame($now, $uuid->getDateTime()->format('Y-m-d H:i'));
            $previous = $uuid;
        }
    }

    public function testLazyStringUuidUuid7EachUuidIsMonotonicallyIncreasing(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $previous = Uuid::uuid7();

        for ($i = 0; $i < 25; $i++) {
            $uuid = Uuid::fromString(Uuid::uuid7()->toString());
            $now = gmdate('Y-m-d H:i');
            $this->assertGreaterThan(0, $uuid->compareTo($previous));
            $this->assertSame($now, $uuid->getDateTime()->format('Y-m-d H:i'));
            $previous = $uuid;
        }
    }

    public function testBytesUuidUuid7EachUuidIsMonotonicallyIncreasing(): void
    {
        $previous = Uuid::uuid7();

        for ($i = 0; $i < 25; $i++) {
            $uuid = Uuid::uuid7();
            $now = gmdate('Y-m-d H:i');
            $this->assertGreaterThan(0, $uuid->compareTo($previous));
            $this->assertSame($now, $uuid->getDateTime()->format('Y-m-d H:i'));
            $previous = $uuid;
        }
    }

    public function testStringUuidUuid7EachUuidFromSameDateTimeIsMonotonicallyIncreasing(): void
    {
        $dateTime = new DateTimeImmutable();
        $previous = Uuid::fromString(Uuid::uuid7($dateTime)->toString());

        for ($i = 0; $i < 25; $i++) {
            $uuid = Uuid::fromString(Uuid::uuid7($dateTime)->toString());
            $this->assertGreaterThan(0, $uuid->compareTo($previous));
            $this->assertSame($dateTime->format('Y-m-d H:i'), $uuid->getDateTime()->format('Y-m-d H:i'));
            $previous = $uuid;
        }
    }

    public function testLazyStringUuidUuid7EachUuidFromSameDateTimeIsMonotonicallyIncreasing(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $dateTime = new DateTimeImmutable();
        $previous = Uuid::fromString(Uuid::uuid7($dateTime)->toString());

        for ($i = 0; $i < 25; $i++) {
            $uuid = Uuid::fromString(Uuid::uuid7($dateTime)->toString());
            $this->assertGreaterThan(0, $uuid->compareTo($previous));
            $this->assertSame($dateTime->format('Y-m-d H:i'), $uuid->getDateTime()->format('Y-m-d H:i'));
            $previous = $uuid;
        }
    }

    public function testBytesUuidUuid7EachUuidFromSameDateTimeIsMonotonicallyIncreasing(): void
    {
        $dateTime = new DateTimeImmutable();
        $previous = Uuid::uuid7($dateTime);

        for ($i = 0; $i < 25; $i++) {
            $uuid = Uuid::uuid7($dateTime);
            $this->assertGreaterThan(0, $uuid->compareTo($previous));
            $this->assertSame($dateTime->format('Y-m-d H:i'), $uuid->getDateTime()->format('Y-m-d H:i'));
            $previous = $uuid;
        }
    }

    public function testStringUuidUuid8(): void
    {
        $uuid = Uuid::fromString(
            Uuid::uuid8("\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff")->toString(),
        );
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_CUSTOM, $uuid->getVersion());
    }

    public function testLazyStringUuidUuid8(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(
            Uuid::uuid8("\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff")->toString(),
        );
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_CUSTOM, $uuid->getVersion());
    }

    public function testBytesUuidUuid8(): void
    {
        $uuid = Uuid::uuid8("\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff");
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_CUSTOM, $uuid->getVersion());
    }

    public function testUuid8ThrowsExceptionForUnsupportedFactory(): void
    {
        $factory = $this->createMock(UuidFactoryInterface::class);

        Uuid::setFactory($factory);

        $this->expectException(UnsupportedOperationException::class);
        $this->expectExceptionMessage('The provided factory does not support the uuid8() method');

        Uuid::uuid8("\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff");
    }

    /**
     * Tests known version-3 UUIDs
     *
     * Taken from the Python UUID tests in
     * http://hg.python.org/cpython/file/2f4c4db9aee5/Lib/test/test_uuid.py
     *
     * @param non-empty-string $uuid
     * @param non-empty-string $ns
     */
    #[DataProvider('provideUuid3WithKnownUuids')]
    public function testStringUuidUuid3WithKnownUuids(string $uuid, string $ns, string $name): void
    {
        $uobj1 = Uuid::fromString(Uuid::uuid3($ns, $name)->toString());
        $uobj2 = Uuid::fromString(Uuid::uuid3(Uuid::fromString($ns), $name)->toString());

        $this->assertSame(Uuid::RFC_4122, $uobj1->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_HASH_MD5, $uobj1->getVersion());
        $this->assertSame(Uuid::fromString($uuid)->toString(), $uobj1->toString());
        $this->assertTrue($uobj1->equals($uobj2));
    }

    /**
     * Tests known version-3 UUIDs
     *
     * Taken from the Python UUID tests in
     * http://hg.python.org/cpython/file/2f4c4db9aee5/Lib/test/test_uuid.py
     *
     * @param non-empty-string $uuid
     * @param non-empty-string $ns
     */
    #[DataProvider('provideUuid3WithKnownUuids')]
    public function testLazyStringUuidUuid3WithKnownUuids(string $uuid, string $ns, string $name): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uobj1 = Uuid::fromString(Uuid::uuid3($ns, $name)->toString());
        $uobj2 = Uuid::fromString(Uuid::uuid3(Uuid::fromString($ns), $name)->toString());

        $this->assertSame(Uuid::RFC_4122, $uobj1->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_HASH_MD5, $uobj1->getVersion());
        $this->assertSame(Uuid::fromString($uuid)->toString(), $uobj1->toString());
        $this->assertTrue($uobj1->equals($uobj2));
    }

    /**
     * Tests known version-3 UUIDs
     *
     * Taken from the Python UUID tests in
     * http://hg.python.org/cpython/file/2f4c4db9aee5/Lib/test/test_uuid.py
     *
     * @param non-empty-string $uuid
     * @param non-empty-string $ns
     */
    #[DataProvider('provideUuid3WithKnownUuids')]
    public function testBytesUuidUuid3WithKnownUuids(string $uuid, string $ns, string $name): void
    {
        $uobj1 = Uuid::uuid3($ns, $name);
        $uobj2 = Uuid::uuid3(Uuid::fromString($ns), $name);

        $this->assertSame(Uuid::RFC_4122, $uobj1->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_HASH_MD5, $uobj1->getVersion());
        $this->assertSame(Uuid::fromString($uuid)->toString(), $uobj1->toString());
        $this->assertTrue($uobj1->equals($uobj2));
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function provideUuid3WithKnownUuids(): array
    {
        /** @noinspection HttpUrlsUsage */
        return [
            [
                'uuid' => '6fa459ea-ee8a-3ca4-894e-db77e160355e',
                'ns' => Uuid::NAMESPACE_DNS,
                'name' => 'python.org',
            ],
            [
                'uuid' => '9fe8e8c4-aaa8-32a9-a55c-4535a88b748d',
                'ns' => Uuid::NAMESPACE_URL,
                'name' => 'http://python.org/',
            ],
            [
                'uuid' => 'dd1a1cef-13d5-368a-ad82-eca71acd4cd1',
                'ns' => Uuid::NAMESPACE_OID,
                'name' => '1.3.6.1',
            ],
            [
                'uuid' => '658d3002-db6b-3040-a1d1-8ddd7d189a4d',
                'ns' => Uuid::NAMESPACE_X500,
                'name' => 'c=ca',
            ],
        ];
    }

    public function testStringUuidUuid4(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid4()->toString());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_RANDOM, $uuid->getVersion());
    }

    public function testLazyStringUuidUuid4(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid4()->toString());
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_RANDOM, $uuid->getVersion());
    }

    public function testBytesUuidUuid4(): void
    {
        $uuid = Uuid::uuid4();
        $this->assertSame(Uuid::RFC_4122, $uuid->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_RANDOM, $uuid->getVersion());
    }

    /**
     * Tests that generated UUID's using timestamp last COMB are sequential
     */
    public function testStringUuidUuid4TimestampLastComb(): void
    {
        $mock = $this->getMockBuilder(RandomGeneratorInterface::class)->getMock();
        $mock
            ->method('generate')
            ->willReturnCallback(function ($length) {
                // Makes first fields of UUIDs equal
                return hex2bin(str_pad('', $length * 2, '0'));
            });

        $featureSet = new FeatureSet();
        $generator = new CombGenerator($mock, $featureSet->getNumberConverter());
        $codec = new TimestampLastCombCodec($featureSet->getBuilder());
        $factory = (new FastUuidFactoryFactory())->createAndSetFactory(randomGenerator: $generator, codec: $codec);

        $previous = $factory->fromString($factory->uuid4()->toString());

        for ($i = 0; $i < 1000; $i++) {
            usleep(100);
            $uuid = $factory->fromString($factory->uuid4()->toString());
            $this->assertGreaterThan($previous->toString(), $uuid->toString());

            $previous = $uuid;
        }
    }

    /**
     * Tests that generated UUID's using timestamp last COMB are sequential
     */
    public function testLazyStringUuidUuid4TimestampLastComb(): void
    {
        $mock = $this->getMockBuilder(RandomGeneratorInterface::class)->getMock();
        $mock
            ->method('generate')
            ->willReturnCallback(function ($length) {
                // Makes first fields of UUIDs equal
                return hex2bin(str_pad('', $length * 2, '0'));
            });

        $featureSet = new FeatureSet();
        $generator = new CombGenerator($mock, $featureSet->getNumberConverter());
        $codec = new TimestampLastCombCodec($featureSet->getBuilder());
        $factory = (new FastUuidFactoryFactory())
            ->createAndSetFactory(useLazy: true, randomGenerator: $generator, codec: $codec);

        $previous = $factory->fromString($factory->uuid4()->toString());

        for ($i = 0; $i < 1000; $i++) {
            usleep(100);
            $uuid = $factory->fromString($factory->uuid4()->toString());
            $this->assertGreaterThan($previous->toString(), $uuid->toString());

            $previous = $uuid;
        }
    }

    /**
     * Tests that generated UUID's using timestamp last COMB are sequential
     */
    public function testBytesUuidUuid4TimestampLastComb(): void
    {
        $mock = $this->getMockBuilder(RandomGeneratorInterface::class)->getMock();
        $mock
            ->method('generate')
            ->willReturnCallback(function ($length) {
                // Makes first fields of UUIDs equal
                return hex2bin(str_pad('', $length * 2, '0'));
            });

        $featureSet = new FeatureSet();
        $generator = new CombGenerator($mock, $featureSet->getNumberConverter());
        $codec = new TimestampLastCombCodec($featureSet->getBuilder());
        $factory = (new FastUuidFactoryFactory())->createAndSetFactory(randomGenerator: $generator, codec: $codec);

        $previous = $factory->uuid4();

        for ($i = 0; $i < 1000; $i++) {
            usleep(100);
            $uuid = $factory->uuid4();
            $this->assertGreaterThan($previous->toString(), $uuid->toString());

            $previous = $uuid;
        }
    }

    /**
     * Tests that generated UUID's using timestamp first COMB are sequential
     */
    public function testStringUuidUuid4TimestampFirstComb(): void
    {
        $mock = $this->getMockBuilder(RandomGeneratorInterface::class)->getMock();
        $mock
            ->method('generate')
            ->willReturnCallback(function ($length) {
                // Makes first fields of UUIDs equal
                return hex2bin(str_pad('', $length * 2, '0'));
            });

        $featureSet = new FeatureSet();
        $generator = new CombGenerator($mock, $featureSet->getNumberConverter());
        $codec = new TimestampFirstCombCodec($featureSet->getBuilder());
        $factory = (new FastUuidFactoryFactory())->createAndSetFactory(randomGenerator: $generator, codec: $codec);

        $previous = $factory->fromString($factory->uuid4()->toString());

        for ($i = 0; $i < 1000; $i++) {
            usleep(100);
            $uuid = $factory->fromString($factory->uuid4()->toString());
            $this->assertGreaterThan($previous->toString(), $uuid->toString());

            $previous = $uuid;
        }
    }

    /**
     * Tests that generated UUID's using timestamp first COMB are sequential
     */
    public function testLazyStringUuidUuid4TimestampFirstComb(): void
    {
        $mock = $this->getMockBuilder(RandomGeneratorInterface::class)->getMock();
        $mock
            ->method('generate')
            ->willReturnCallback(function ($length) {
                // Makes first fields of UUIDs equal
                return hex2bin(str_pad('', $length * 2, '0'));
            });

        $featureSet = new FeatureSet();
        $generator = new CombGenerator($mock, $featureSet->getNumberConverter());
        $codec = new TimestampFirstCombCodec($featureSet->getBuilder());
        $factory = (new FastUuidFactoryFactory())
            ->createAndSetFactory(useLazy: true, randomGenerator: $generator, codec: $codec);

        $previous = $factory->fromString($factory->uuid4()->toString());

        for ($i = 0; $i < 1000; $i++) {
            usleep(100);
            $uuid = $factory->fromString($factory->uuid4()->toString());
            $this->assertGreaterThan($previous->toString(), $uuid->toString());

            $previous = $uuid;
        }
    }

    /**
     * Tests that generated UUID's using timestamp first COMB are sequential
     */
    public function testBytesUuidUuid4TimestampFirstComb(): void
    {
        $mock = $this->getMockBuilder(RandomGeneratorInterface::class)->getMock();
        $mock
            ->method('generate')
            ->willReturnCallback(function ($length) {
                // Makes first fields of UUIDs equal
                return hex2bin(str_pad('', $length * 2, '0'));
            });

        $featureSet = new FeatureSet();
        $generator = new CombGenerator($mock, $featureSet->getNumberConverter());
        $codec = new TimestampFirstCombCodec($featureSet->getBuilder());
        $factory = (new FastUuidFactoryFactory())->createAndSetFactory(randomGenerator: $generator, codec: $codec);

        $previous = $factory->uuid4();

        for ($i = 0; $i < 1000; $i++) {
            usleep(100);
            $uuid = $factory->uuid4();
            $this->assertGreaterThan($previous->toString(), $uuid->toString());

            $previous = $uuid;
        }
    }

    /**
     * Test that COMB UUID's have a version 4 flag
     */
    public function testStringUuidUuid4CombVersion(): void
    {
        $featureSet = new FeatureSet();
        $generator = new CombGenerator(
            (new RandomGeneratorFactory())->getGenerator(),
            $featureSet->getNumberConverter()
        );

        $factory = (new FastUuidFactoryFactory())->createAndSetFactory(randomGenerator: $generator);

        $uuid = $factory->fromString($factory->uuid4()->toString());

        $this->assertSame(Uuid::UUID_TYPE_RANDOM, $uuid->getVersion());
    }

    /**
     * Test that COMB UUID's have a version 4 flag
     */
    public function testLazyStringUuidUuid4CombVersion(): void
    {
        $featureSet = new FeatureSet();
        $generator = new CombGenerator(
            (new RandomGeneratorFactory())->getGenerator(),
            $featureSet->getNumberConverter()
        );

        $factory = (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true, randomGenerator: $generator);

        $uuid = $factory->fromString($factory->uuid4()->toString());

        $this->assertSame(Uuid::UUID_TYPE_RANDOM, $uuid->getVersion());
    }

    /**
     * Test that COMB UUID's have a version 4 flag
     */
    public function testBytesUuidUuid4CombVersion(): void
    {
        $featureSet = new FeatureSet();
        $generator = new CombGenerator(
            (new RandomGeneratorFactory())->getGenerator(),
            $featureSet->getNumberConverter()
        );

        $factory = (new FastUuidFactoryFactory())->createAndSetFactory(randomGenerator: $generator);

        $uuid = $factory->uuid4();

        $this->assertSame(Uuid::UUID_TYPE_RANDOM, $uuid->getVersion());
    }

    /**
     * Tests known version-5 UUIDs
     *
     * Taken from the Python UUID tests in
     * http://hg.python.org/cpython/file/2f4c4db9aee5/Lib/test/test_uuid.py
     *
     * @param non-empty-string $uuid
     * @param non-empty-string $ns
     */
    #[DataProvider('provideUuid5WithKnownUuids')]
    public function testStringUuidUuid5WithKnownUuids(string $uuid, string $ns, string $name): void
    {
        $uobj1 = Uuid::fromString(Uuid::uuid5($ns, $name)->toString());
        $uobj2 = Uuid::fromString(Uuid::uuid5(Uuid::fromString($ns), $name)->toString());

        $this->assertSame(Uuid::RFC_4122, $uobj1->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_HASH_SHA1, $uobj1->getVersion());
        $this->assertSame(Uuid::fromString($uuid)->toString(), $uobj1->toString());
        $this->assertTrue($uobj1->equals($uobj2));
    }

    /**
     * Tests known version-5 UUIDs
     *
     * Taken from the Python UUID tests in
     * http://hg.python.org/cpython/file/2f4c4db9aee5/Lib/test/test_uuid.py
     *
     * @param non-empty-string $uuid
     * @param non-empty-string $ns
     */
    #[DataProvider('provideUuid5WithKnownUuids')]
    public function testLazyStringUuidUuid5WithKnownUuids(string $uuid, string $ns, string $name): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uobj1 = Uuid::fromString(Uuid::uuid5($ns, $name)->toString());
        $uobj2 = Uuid::fromString(Uuid::uuid5(Uuid::fromString($ns), $name)->toString());

        $this->assertSame(Uuid::RFC_4122, $uobj1->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_HASH_SHA1, $uobj1->getVersion());
        $this->assertSame(Uuid::fromString($uuid)->toString(), $uobj1->toString());
        $this->assertTrue($uobj1->equals($uobj2));
    }

    /**
     * Tests known version-5 UUIDs
     *
     * Taken from the Python UUID tests in
     * http://hg.python.org/cpython/file/2f4c4db9aee5/Lib/test/test_uuid.py
     *
     * @param non-empty-string $uuid
     * @param non-empty-string $ns
     */
    #[DataProvider('provideUuid5WithKnownUuids')]
    public function testBytesUuidUuid5WithKnownUuids(string $uuid, string $ns, string $name): void
    {
        $uobj1 = Uuid::uuid5($ns, $name);
        $uobj2 = Uuid::uuid5(Uuid::fromString($ns), $name);

        $this->assertSame(Uuid::RFC_4122, $uobj1->getVariant());
        $this->assertSame(Uuid::UUID_TYPE_HASH_SHA1, $uobj1->getVersion());
        $this->assertSame(Uuid::fromString($uuid)->toString(), $uobj1->toString());
        $this->assertTrue($uobj1->equals($uobj2));
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function provideUuid5WithKnownUuids(): array
    {
        /** @noinspection HttpUrlsUsage */
        return [
            [
                'uuid' => '886313e1-3b8a-5372-9b90-0c9aee199e5d',
                'ns' => Uuid::NAMESPACE_DNS,
                'name' => 'python.org',
            ],
            [
                'uuid' => '4c565f0d-3f5a-5890-b41b-20cf47701c5e',
                'ns' => Uuid::NAMESPACE_URL,
                'name' => 'http://python.org/',
            ],
            [
                'uuid' => '1447fa61-5277-5fef-a9b3-fbc6e44f4af3',
                'ns' => Uuid::NAMESPACE_OID,
                'name' => '1.3.6.1',
            ],
            [
                'uuid' => 'cc957dd1-a972-5349-98cd-874190002798',
                'ns' => Uuid::NAMESPACE_X500,
                'name' => 'c=ca',
            ],
        ];
    }

    public function testStringUuidCompareTo(): void
    {
        // $uuid1 and $uuid2 are identical
        $uuid1 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $uuid2 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        // The next three UUIDs are used for comparing msb and lsb in
        // the compareTo() method

        // msb are less than $uuid4, lsb are greater than $uuid5
        $uuid3 = Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f4');

        // msb are greater than $uuid3, lsb are equal to those in $uuid3
        $uuid4 = Uuid::fromString('44cca71e-d13d-11e2-a959-c8bcc8a476f4');

        // msb are equal to those in $uuid3, lsb are less than in $uuid3
        $uuid5 = Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f3');

        $this->assertSame(0, $uuid1->compareTo($uuid2));
        $this->assertSame(0, $uuid2->compareTo($uuid1));
        $this->assertSame(-1, $uuid3->compareTo($uuid4));
        $this->assertSame(1, $uuid4->compareTo($uuid3));
        $this->assertSame(-1, $uuid5->compareTo($uuid3));
        $this->assertSame(1, $uuid3->compareTo($uuid5));
    }

    public function testLazyStringUuidCompareTo(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        // $uuid1 and $uuid2 are identical
        $uuid1 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $uuid2 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');

        // The next three UUIDs are used for comparing msb and lsb in
        // the compareTo() method

        // msb are less than $uuid4, lsb are greater than $uuid5
        $uuid3 = Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f4');

        // msb are greater than $uuid3, lsb are equal to those in $uuid3
        $uuid4 = Uuid::fromString('44cca71e-d13d-11e2-a959-c8bcc8a476f4');

        // msb are equal to those in $uuid3, lsb are less than in $uuid3
        $uuid5 = Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f3');

        $this->assertSame(0, $uuid1->compareTo($uuid2));
        $this->assertSame(0, $uuid2->compareTo($uuid1));
        $this->assertSame(-1, $uuid3->compareTo($uuid4));
        $this->assertSame(1, $uuid4->compareTo($uuid3));
        $this->assertSame(-1, $uuid5->compareTo($uuid3));
        $this->assertSame(1, $uuid3->compareTo($uuid5));
    }

    public function testBytesUuidCompareTo(): void
    {
        // $uuid1 and $uuid2 are identical
        $uuid1 = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $uuid2 = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());

        // The next three UUIDs are used for comparing msb and lsb in
        // the compareTo() method

        // msb are less than $uuid4, lsb are greater than $uuid5
        $uuid3 = Uuid::fromBytes(Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f4')->getBytes());

        // msb are greater than $uuid3, lsb are equal to those in $uuid3
        $uuid4 = Uuid::fromBytes(Uuid::fromString('44cca71e-d13d-11e2-a959-c8bcc8a476f4')->getBytes());

        // msb are equal to those in $uuid3, lsb are less than in $uuid3
        $uuid5 = Uuid::fromBytes(Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f3')->getBytes());

        $this->assertSame(0, $uuid1->compareTo($uuid2));
        $this->assertSame(0, $uuid2->compareTo($uuid1));
        $this->assertSame(-1, $uuid3->compareTo($uuid4));
        $this->assertSame(1, $uuid4->compareTo($uuid3));
        $this->assertSame(-1, $uuid5->compareTo($uuid3));
        $this->assertSame(1, $uuid3->compareTo($uuid5));
    }

    public function testCompareToBytesWithStringUuid(): void
    {
        // $uuid1 and $uuid2 are identical
        $stringUuid1 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $bytesUuid1 = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $stringUuid2 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $bytesUuid2 = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());

        // The next three UUIDs are used for comparing msb and lsb in
        // the compareTo() method

        // msb are less than $uuid4, lsb are greater than $uuid5
        $stringUuid3 = Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f4');
        $bytesUuid3 = Uuid::fromBytes(Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f4')->getBytes());

        // msb are greater than $uuid3, lsb are equal to those in $uuid3
        $stringUuid4 = Uuid::fromString('44cca71e-d13d-11e2-a959-c8bcc8a476f4');
        $bytesUuid4 = Uuid::fromBytes(Uuid::fromString('44cca71e-d13d-11e2-a959-c8bcc8a476f4')->getBytes());

        // msb are equal to those in $uuid3, lsb are less than in $uuid3
        $stringUuid5 = Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f3');
        $bytesUuid5 = Uuid::fromBytes(Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f3')->getBytes());

        $this->assertSame(0, $stringUuid1->compareTo($bytesUuid2));
        $this->assertSame(0, $bytesUuid1->compareTo($stringUuid2));
        $this->assertSame(0, $stringUuid2->compareTo($bytesUuid1));
        $this->assertSame(0, $bytesUuid2->compareTo($stringUuid1));
        $this->assertSame(-1, $stringUuid3->compareTo($bytesUuid4));
        $this->assertSame(-1, $bytesUuid3->compareTo($stringUuid4));
        $this->assertSame(1, $stringUuid4->compareTo($bytesUuid3));
        $this->assertSame(1, $bytesUuid4->compareTo($stringUuid3));
        $this->assertSame(-1, $stringUuid5->compareTo($bytesUuid3));
        $this->assertSame(-1, $bytesUuid5->compareTo($stringUuid3));
        $this->assertSame(1, $stringUuid3->compareTo($bytesUuid5));
        $this->assertSame(1, $bytesUuid3->compareTo($stringUuid5));
    }

    public function testLazyCompareToBytesWithStringUuid(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        // $uuid1 and $uuid2 are identical
        $stringUuid1 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $bytesUuid1 = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());
        $stringUuid2 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $bytesUuid2 = Uuid::fromBytes(Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66')->getBytes());

        // The next three UUIDs are used for comparing msb and lsb in
        // the compareTo() method

        // msb are less than $uuid4, lsb are greater than $uuid5
        $stringUuid3 = Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f4');
        $bytesUuid3 = Uuid::fromBytes(Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f4')->getBytes());

        // msb are greater than $uuid3, lsb are equal to those in $uuid3
        $stringUuid4 = Uuid::fromString('44cca71e-d13d-11e2-a959-c8bcc8a476f4');
        $bytesUuid4 = Uuid::fromBytes(Uuid::fromString('44cca71e-d13d-11e2-a959-c8bcc8a476f4')->getBytes());

        // msb are equal to those in $uuid3, lsb are less than in $uuid3
        $stringUuid5 = Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f3');
        $bytesUuid5 = Uuid::fromBytes(Uuid::fromString('44cca71e-d13d-11e1-a959-c8bcc8a476f3')->getBytes());

        $this->assertSame(0, $stringUuid1->compareTo($bytesUuid2));
        $this->assertSame(0, $bytesUuid1->compareTo($stringUuid2));
        $this->assertSame(0, $stringUuid2->compareTo($bytesUuid1));
        $this->assertSame(0, $bytesUuid2->compareTo($stringUuid1));
        $this->assertSame(-1, $stringUuid3->compareTo($bytesUuid4));
        $this->assertSame(-1, $bytesUuid3->compareTo($stringUuid4));
        $this->assertSame(1, $stringUuid4->compareTo($bytesUuid3));
        $this->assertSame(1, $bytesUuid4->compareTo($stringUuid3));
        $this->assertSame(-1, $stringUuid5->compareTo($bytesUuid3));
        $this->assertSame(-1, $bytesUuid5->compareTo($stringUuid3));
        $this->assertSame(1, $stringUuid3->compareTo($bytesUuid5));
        $this->assertSame(1, $bytesUuid3->compareTo($stringUuid5));
    }

    public function testCompareToReturnsZeroWhenDifferentCases(): void
    {
        $uuidString = 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66';
        // $uuid1 and $uuid2 are identical
        $uuid1 = Uuid::fromString($uuidString);
        $uuid2 = Uuid::fromString(strtoupper($uuidString));

        $this->assertSame(0, $uuid1->compareTo($uuid2));
        $this->assertSame(0, $uuid2->compareTo($uuid1));
    }

    public function testEqualsReturnsTrueWhenDifferentCases(): void
    {
        $uuidString = 'ff6f8cb0-c57d-11e1-9b21-0800200c9a66';
        // $uuid1 and $uuid2 are identical
        $uuid1 = Uuid::fromString($uuidString);
        $uuid2 = Uuid::fromString(strtoupper($uuidString));

        $this->assertTrue($uuid1->equals($uuid2));
        $this->assertTrue($uuid2->equals($uuid1));
    }

    public function testStringUuidEquals(): void
    {
        $uuid1 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $uuid2 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $uuid3 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a67');

        $this->assertTrue($uuid1->equals($uuid2));
        $this->assertFalse($uuid1->equals($uuid3));
        $this->assertFalse($uuid1->equals(new stdClass()));
    }

    public function testLazyStringUuidEquals(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid1 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $uuid2 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $uuid3 = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a67');

        $this->assertTrue($uuid1->equals($uuid2));
        $this->assertFalse($uuid1->equals($uuid3));
        $this->assertFalse($uuid1->equals(new stdClass()));
    }

    public function testBytesUuidEquals(): void
    {
        $uuid1 = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'python.org');
        $uuid2 = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'python.org');
        $uuid3 = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'php.net');

        $this->assertTrue($uuid1->equals($uuid2));
        $this->assertFalse($uuid1->equals($uuid3));
        $this->assertFalse($uuid1->equals(new stdClass()));
    }

    public function testStringUuidCalculateUuidTime(): void
    {
        $timeOfDay = new FixedTimeProvider(new Time(1348845514, 277885));

        $featureSet = new FeatureSet();
        $featureSet->setTimeProvider($timeOfDay);

        // For usec = 277885
        Uuid::setFactory((new FastUuidFactoryFactory())->create(features: $featureSet));
        $uuidA = Uuid::fromString(Uuid::uuid1(0x00007ffffffe, 0x1669)->toString());

        $this->assertSame('c4dbe7e2-097f-11e2-9669-00007ffffffe', (string) $uuidA);
        $this->assertSame('c4dbe7e2', $uuidA->getTimeLowHex());
        $this->assertSame('097f', $uuidA->getTimeMidHex());
        $this->assertSame('11e2', $uuidA->getTimeHiAndVersionHex());

        // For usec = 0
        $timeOfDay->setUsec(0);
        $uuidB = Uuid::fromString(Uuid::uuid1(0x00007ffffffe, 0x1669)->toString());

        $this->assertSame('c4b18100-097f-11e2-9669-00007ffffffe', (string) $uuidB);
        $this->assertSame('c4b18100', $uuidB->getTimeLowHex());
        $this->assertSame('097f', $uuidB->getTimeMidHex());
        $this->assertSame('11e2', $uuidB->getTimeHiAndVersionHex());

        // For usec = 999999
        $timeOfDay->setUsec(999999);
        $uuidC = Uuid::fromString(Uuid::uuid1(0x00007ffffffe, 0x1669)->toString());

        $this->assertSame('c54a1776-097f-11e2-9669-00007ffffffe', (string) $uuidC);
        $this->assertSame('c54a1776', $uuidC->getTimeLowHex());
        $this->assertSame('097f', $uuidC->getTimeMidHex());
        $this->assertSame('11e2', $uuidC->getTimeHiAndVersionHex());
    }

    public function testLazyStringUuidCalculateUuidTime(): void
    {
        $timeOfDay = new FixedTimeProvider(new Time(1348845514, 277885));

        $featureSet = new FeatureSet();
        $featureSet->setTimeProvider($timeOfDay);

        // For usec = 277885
        (new FastUuidFactoryFactory())->createAndSetFactory(features: $featureSet, useLazy: true);
        $uuidA = Uuid::fromString(Uuid::uuid1(0x00007ffffffe, 0x1669)->toString());

        $this->assertSame('c4dbe7e2-097f-11e2-9669-00007ffffffe', (string) $uuidA);
        $this->assertSame('c4dbe7e2', $uuidA->getTimeLowHex());
        $this->assertSame('097f', $uuidA->getTimeMidHex());
        $this->assertSame('11e2', $uuidA->getTimeHiAndVersionHex());

        // For usec = 0
        $timeOfDay->setUsec(0);
        $uuidB = Uuid::fromString(Uuid::uuid1(0x00007ffffffe, 0x1669)->toString());

        $this->assertSame('c4b18100-097f-11e2-9669-00007ffffffe', (string) $uuidB);
        $this->assertSame('c4b18100', $uuidB->getTimeLowHex());
        $this->assertSame('097f', $uuidB->getTimeMidHex());
        $this->assertSame('11e2', $uuidB->getTimeHiAndVersionHex());

        // For usec = 999999
        $timeOfDay->setUsec(999999);
        $uuidC = Uuid::fromString(Uuid::uuid1(0x00007ffffffe, 0x1669)->toString());

        $this->assertSame('c54a1776-097f-11e2-9669-00007ffffffe', (string) $uuidC);
        $this->assertSame('c54a1776', $uuidC->getTimeLowHex());
        $this->assertSame('097f', $uuidC->getTimeMidHex());
        $this->assertSame('11e2', $uuidC->getTimeHiAndVersionHex());
    }

    public function testBytesUuidCalculateUuidTime(): void
    {
        $timeOfDay = new FixedTimeProvider(new Time(1348845514, 277885));

        $featureSet = new FeatureSet();
        $featureSet->setTimeProvider($timeOfDay);

        // For usec = 277885
        Uuid::setFactory((new FastUuidFactoryFactory())->create(features: $featureSet));
        $uuidA = Uuid::uuid1(0x00007ffffffe, 0x1669);

        $this->assertSame('c4dbe7e2-097f-11e2-9669-00007ffffffe', (string) $uuidA);
        $this->assertSame('c4dbe7e2', $uuidA->getTimeLowHex());
        $this->assertSame('097f', $uuidA->getTimeMidHex());
        $this->assertSame('11e2', $uuidA->getTimeHiAndVersionHex());

        // For usec = 0
        $timeOfDay->setUsec(0);
        $uuidB = Uuid::uuid1(0x00007ffffffe, 0x1669);

        $this->assertSame('c4b18100-097f-11e2-9669-00007ffffffe', (string) $uuidB);
        $this->assertSame('c4b18100', $uuidB->getTimeLowHex());
        $this->assertSame('097f', $uuidB->getTimeMidHex());
        $this->assertSame('11e2', $uuidB->getTimeHiAndVersionHex());

        // For usec = 999999
        $timeOfDay->setUsec(999999);
        $uuidC = Uuid::uuid1(0x00007ffffffe, 0x1669);

        $this->assertSame('c54a1776-097f-11e2-9669-00007ffffffe', (string) $uuidC);
        $this->assertSame('c54a1776', $uuidC->getTimeLowHex());
        $this->assertSame('097f', $uuidC->getTimeMidHex());
        $this->assertSame('11e2', $uuidC->getTimeHiAndVersionHex());
    }

    public function testStringUuidCalculateUuidTimeUpperLowerBounds(): void
    {
        // 5235-03-31T21:20:59+00:00
        $timeOfDay = new FixedTimeProvider(new Time('103072857659', '999999'));

        $featureSet = new FeatureSet();
        $featureSet->setTimeProvider($timeOfDay);

        Uuid::setFactory((new FastUuidFactoryFactory())->create(features: $featureSet));
        $uuidA = Uuid::fromString(Uuid::uuid1(0x00007ffffffe, 0x1669)->toString());

        $this->assertSame('ff9785f6-ffff-1fff-9669-00007ffffffe', (string) $uuidA);
        $this->assertSame('ff9785f6', $uuidA->getTimeLowHex());
        $this->assertSame('ffff', $uuidA->getTimeMidHex());
        $this->assertSame('1fff', $uuidA->getTimeHiAndVersionHex());

        // 1582-10-15T00:00:00+00:00
        $timeOfDay = new FixedTimeProvider(new Time('-12219292800', '0'));

        $featureSet->setTimeProvider($timeOfDay);

        Uuid::setFactory((new FastUuidFactoryFactory())->create(features: $featureSet));
        $uuidB = Uuid::fromString(Uuid::uuid1(0x00007ffffffe, 0x1669)->toString());

        $this->assertSame('00000000-0000-1000-9669-00007ffffffe', (string) $uuidB);
        $this->assertSame('00000000', $uuidB->getTimeLowHex());
        $this->assertSame('0000', $uuidB->getTimeMidHex());
        $this->assertSame('1000', $uuidB->getTimeHiAndVersionHex());
    }

    public function testLazyStringUuidCalculateUuidTimeUpperLowerBounds(): void
    {
        // 5235-03-31T21:20:59+00:00
        $timeOfDay = new FixedTimeProvider(new Time('103072857659', '999999'));

        $featureSet = new FeatureSet();
        $featureSet->setTimeProvider($timeOfDay);

        (new FastUuidFactoryFactory())->createAndSetFactory(features: $featureSet, useLazy: true);
        $uuidA = Uuid::fromString(Uuid::uuid1(0x00007ffffffe, 0x1669)->toString());

        $this->assertSame('ff9785f6-ffff-1fff-9669-00007ffffffe', (string) $uuidA);
        $this->assertSame('ff9785f6', $uuidA->getTimeLowHex());
        $this->assertSame('ffff', $uuidA->getTimeMidHex());
        $this->assertSame('1fff', $uuidA->getTimeHiAndVersionHex());

        // 1582-10-15T00:00:00+00:00
        $timeOfDay = new FixedTimeProvider(new Time('-12219292800', '0'));

        $featureSet->setTimeProvider($timeOfDay);

        (new FastUuidFactoryFactory())->createAndSetFactory(features: $featureSet, useLazy: true);
        $uuidB = Uuid::fromString(Uuid::uuid1(0x00007ffffffe, 0x1669)->toString());

        $this->assertSame('00000000-0000-1000-9669-00007ffffffe', (string) $uuidB);
        $this->assertSame('00000000', $uuidB->getTimeLowHex());
        $this->assertSame('0000', $uuidB->getTimeMidHex());
        $this->assertSame('1000', $uuidB->getTimeHiAndVersionHex());
    }

    public function testBytesUuidCalculateUuidTimeUpperLowerBounds(): void
    {
        // 5235-03-31T21:20:59+00:00
        $timeOfDay = new FixedTimeProvider(new Time('103072857659', '999999'));

        $featureSet = new FeatureSet();
        $featureSet->setTimeProvider($timeOfDay);

        Uuid::setFactory((new FastUuidFactoryFactory())->create(features: $featureSet));
        $uuidA = Uuid::uuid1(0x00007ffffffe, 0x1669);

        $this->assertSame('ff9785f6-ffff-1fff-9669-00007ffffffe', (string) $uuidA);
        $this->assertSame('ff9785f6', $uuidA->getTimeLowHex());
        $this->assertSame('ffff', $uuidA->getTimeMidHex());
        $this->assertSame('1fff', $uuidA->getTimeHiAndVersionHex());

        // 1582-10-15T00:00:00+00:00
        $timeOfDay = new FixedTimeProvider(new Time('-12219292800', '0'));

        $featureSet->setTimeProvider($timeOfDay);

        Uuid::setFactory((new FastUuidFactoryFactory())->create(features: $featureSet));
        $uuidB = Uuid::uuid1(0x00007ffffffe, 0x1669);

        $this->assertSame('00000000-0000-1000-9669-00007ffffffe', (string) $uuidB);
        $this->assertSame('00000000', $uuidB->getTimeLowHex());
        $this->assertSame('0000', $uuidB->getTimeMidHex());
        $this->assertSame('1000', $uuidB->getTimeHiAndVersionHex());
    }

    /**
     * Iterates over a 3600-second period and tests to ensure that, for each
     * second in the period, the 32-bit and 64-bit versions of the UUID match
     */
    public function testStringUuid32BitMatch64BitForOneHourPeriod(): void
    {
        $currentTime = strtotime('2012-12-11T00:00:00+00:00');
        $endTime = $currentTime + 3600;

        $timeOfDay = new FixedTimeProvider(new Time($currentTime, 0));

        $smallIntFeatureSet = new FeatureSet(false, true);
        $smallIntFeatureSet->setTimeProvider($timeOfDay);

        $smallIntFactory = (new FastUuidFactoryFactory())->create(features: $smallIntFeatureSet);

        $featureSet = new FeatureSet();
        $featureSet->setTimeProvider($timeOfDay);

        $factory = (new FastUuidFactoryFactory())->create(features: $featureSet);

        while ($currentTime <= $endTime) {
            foreach ([0, 50000, 250000, 500000, 750000, 999999] as $usec) {
                $timeOfDay->setSec($currentTime);
                $timeOfDay->setUsec($usec);

                $uuid32 = $smallIntFactory
                    ->fromString($smallIntFactory->uuid1(0x00007ffffffe, 0x1669)->toString());
                $uuid64 = $factory->fromString($factory->uuid1(0x00007ffffffe, 0x1669)->toString());

                $this->assertTrue(
                    $uuid32->equals($uuid64),
                    'Breaks at ' . gmdate('r', $currentTime)
                    . "; 32-bit: {$uuid32->toString()}, 64-bit: {$uuid64->toString()}"
                );

                // Assert that the time matches
                $usecAdd = BigDecimal::of($usec)->dividedBy('1000000', 14, RoundingMode::HALF_UP);
                $testTime = BigDecimal::of($currentTime)->plus($usecAdd)->toScale(0, RoundingMode::DOWN);
                $this->assertSame((string) $testTime, (string) $uuid64->getDateTime()->getTimestamp());
                $this->assertSame((string) $testTime, (string) $uuid32->getDateTime()->getTimestamp());
            }

            $currentTime++;
        }
    }

    /**
     * Iterates over a 3600-second period and tests to ensure that, for each
     * second in the period, the 32-bit and 64-bit versions of the UUID match
     */
    public function testBytesUuid32BitMatch64BitForOneHourPeriod(): void
    {
        $currentTime = strtotime('2012-12-11T00:00:00+00:00');
        $endTime = $currentTime + 3600;

        $timeOfDay = new FixedTimeProvider(new Time($currentTime, 0));

        $smallIntFeatureSet = new FeatureSet(false, true);
        $smallIntFeatureSet->setTimeProvider($timeOfDay);

        $smallIntFactory = (new FastUuidFactoryFactory())->create(features: $smallIntFeatureSet);

        $featureSet = new FeatureSet();
        $featureSet->setTimeProvider($timeOfDay);

        $factory = (new FastUuidFactoryFactory())->create(features: $featureSet);

        while ($currentTime <= $endTime) {
            foreach ([0, 50000, 250000, 500000, 750000, 999999] as $usec) {
                $timeOfDay->setSec($currentTime);
                $timeOfDay->setUsec($usec);

                $uuid32 = $smallIntFactory->uuid1(0x00007ffffffe, 0x1669);
                $uuid64 = $factory->uuid1(0x00007ffffffe, 0x1669);

                $this->assertTrue(
                    $uuid32->equals($uuid64),
                    'Breaks at ' . gmdate('r', $currentTime)
                    . "; 32-bit: {$uuid32->toString()}, 64-bit: {$uuid64->toString()}"
                );

                // Assert that the time matches
                $usecAdd = BigDecimal::of($usec)->dividedBy('1000000', 14, RoundingMode::HALF_UP);
                $testTime = BigDecimal::of($currentTime)->plus($usecAdd)->toScale(0, RoundingMode::DOWN);
                $this->assertSame((string) $testTime, (string) $uuid64->getDateTime()->getTimestamp());
                $this->assertSame((string) $testTime, (string) $uuid32->getDateTime()->getTimestamp());
            }

            $currentTime++;
        }
    }

    /**
     * This method should respond to the result of the factory
     */
    public function testIsValid(): void
    {
        /** @noinspection NonSecureUniqidUsageInspection */
        $argument = uniqid('passed argument ');

        $validator = $this->getMockBuilder(ValidatorInterface::class)->getMock();
        $validator->expects($this->once())->method('validate')->with($argument)->willReturn(true);

        $featureSet = new FeatureSet();
        $featureSet->setValidator($validator);
        (new FastUuidFactoryFactory())->createAndSetFactory($featureSet);

        try {
            $this->assertTrue(Uuid::isValid($argument));
        } finally {
            // reset the static validator
            (new FastUuidFactoryFactory())->createAndSetFactory();
        }
    }

    public function testUsingNilAsValidUuid(): void
    {
        self::assertSame(
            '0cb17687-6ec7-324b-833a-f1d101a7edb7',
            Uuid::uuid3(Uuid::NIL, 'randomtext')
                ->toString()
        );
        self::assertSame(
            '3b24c15b-1273-5628-ade4-fc67c6ede500',
            Uuid::uuid5(Uuid::NIL, 'randomtext')
                ->toString()
        );
    }

    public function testLazyUsingNilAsValidUuid(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        self::assertSame(
            '0cb17687-6ec7-324b-833a-f1d101a7edb7',
            Uuid::uuid3(Uuid::NIL, 'randomtext')
                ->toString()
        );
        self::assertSame(
            '3b24c15b-1273-5628-ade4-fc67c6ede500',
            Uuid::uuid5(Uuid::NIL, 'randomtext')
                ->toString()
        );
    }

    public function testFromBytes(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $bytes = $uuid->getBytes();

        $fromBytesUuid = Uuid::fromBytes($bytes);

        $this->assertTrue($uuid->equals($fromBytesUuid));
    }

    public function testFromBytesArgumentTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Uuid::fromBytes('thisisveryshort');
    }

    public function testFromBytesArgumentTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Uuid::fromBytes('thisisabittoolong');
    }

    public function testFromInteger(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $integer = $uuid->getInteger()->toString();

        $fromIntegerUuid = Uuid::fromInteger($integer);

        $this->assertTrue($uuid->equals($fromIntegerUuid));
    }

    public function testLazyFromInteger(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $integer = $uuid->getInteger()->toString();

        $fromIntegerUuid = Uuid::fromInteger($integer);

        $this->assertTrue($uuid->equals($fromIntegerUuid));
    }

    public function testFromDateTime(): void
    {
        /** @var FastStringUuid $uuid */
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-8b21-0800200c9a66');
        $dateTime = $uuid->getDateTime();

        $fromDateTimeUuid = Uuid::fromDateTime($dateTime, new Hexadecimal('0800200c9a66'), 2849);

        $this->assertTrue($uuid->equals($fromDateTimeUuid));
    }

    /**
     * This test ensures that Ramsey\Uuid passes the same test cases
     * as the Python UUID library.
     *
     * @param non-empty-string $string
     * @param non-empty-string $hex
     * @param string[] $fields
     * @param non-empty-string $urn
     */
    #[DataProvider('providePythonTests')]
    public function testUuidPassesPythonTests(
        string $string,
        string $hex,
        string $bytes,
        string $int,
        array $fields,
        string $urn,
        string $time,
        string $clockSeq,
        int $variant,
        ?int $version
    ): void {
        /** @var UuidInterface[] $uuids */
        $uuids = [
            Uuid::fromString($string),
            Uuid::fromBytes(base64_decode($bytes)),
            Uuid::fromInteger($int),
        ];

        foreach ($uuids as $uuid) {
            $this->assertSame($string, $uuid->toString());
            $this->assertSame($hex, $uuid->getHex()->toString());
            $this->assertSame(base64_decode($bytes), $uuid->getBytes());
            $this->assertSame($int, $uuid->getInteger()->toString());
            $this->assertSame($fields, $uuid->getFieldsHex());
            $this->assertSame($fields['time_low'], $uuid->getTimeLowHex());
            $this->assertSame($fields['time_mid'], $uuid->getTimeMidHex());
            $this->assertSame($fields['time_hi_and_version'], $uuid->getTimeHiAndVersionHex());
            $this->assertSame($fields['clock_seq_hi_and_reserved'], $uuid->getClockSeqHiAndReservedHex());
            $this->assertSame($fields['clock_seq_low'], $uuid->getClockSeqLowHex());
            $this->assertSame($fields['node'], $uuid->getNodeHex());
            $this->assertSame($urn, $uuid->getUrn());
            if ($uuid->getVersion() === Uuid::UUID_TYPE_TIME) {
                $this->assertSame($time, $uuid->getTimestampHex());
            }
            $this->assertSame($clockSeq, $uuid->getClockSequenceHex());
            $this->assertSame($variant, $uuid->getVariant());
            $this->assertSame($version, $uuid->getVersion());
        }
    }

    /**
     * This test ensures that Ramsey\Uuid passes the same test cases
     * as the Python UUID library.
     *
     * @param non-empty-string $string
     * @param non-empty-string $hex
     * @param string[] $fields
     * @param non-empty-string $urn
     */
    #[DataProvider('providePythonTests')]
    public function testLazyUuidPassesPythonTests(
        string $string,
        string $hex,
        string $bytes,
        string $int,
        array $fields,
        string $urn,
        string $time,
        string $clockSeq,
        int $variant,
        ?int $version
    ): void {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        /** @var UuidInterface[] $uuids */
        $uuids = [
            Uuid::fromString($string),
            Uuid::fromBytes(base64_decode($bytes)),
            Uuid::fromInteger($int),
        ];

        foreach ($uuids as $uuid) {
            $this->assertSame($string, $uuid->toString());
            $this->assertSame($hex, $uuid->getHex()->toString());
            $this->assertSame(base64_decode($bytes), $uuid->getBytes());
            $this->assertSame($int, $uuid->getInteger()->toString());
            $this->assertSame($fields, $uuid->getFieldsHex());
            $this->assertSame($fields['time_low'], $uuid->getTimeLowHex());
            $this->assertSame($fields['time_mid'], $uuid->getTimeMidHex());
            $this->assertSame($fields['time_hi_and_version'], $uuid->getTimeHiAndVersionHex());
            $this->assertSame($fields['clock_seq_hi_and_reserved'], $uuid->getClockSeqHiAndReservedHex());
            $this->assertSame($fields['clock_seq_low'], $uuid->getClockSeqLowHex());
            $this->assertSame($fields['node'], $uuid->getNodeHex());
            $this->assertSame($urn, $uuid->getUrn());
            if ($uuid->getVersion() === Uuid::UUID_TYPE_TIME) {
                $this->assertSame($time, $uuid->getTimestampHex());
            }
            $this->assertSame($clockSeq, $uuid->getClockSequenceHex());
            $this->assertSame($variant, $uuid->getVariant());
            $this->assertSame($version, $uuid->getVersion());
        }
    }

    /**
     * Taken from the Python UUID tests in
     * http://hg.python.org/cpython/file/2f4c4db9aee5/Lib/test/test_uuid.py
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function providePythonTests(): array
    {
        // This array is taken directly from the Python tests, more or less.
        return [
            [
                'string' => '00000000-0000-0000-0000-000000000000',
                'hex' => '00000000000000000000000000000000',
                'bytes' => 'AAAAAAAAAAAAAAAAAAAAAA==',
                'int' => '0',
                'fields' => [
                    'time_low' => '00000000',
                    'time_mid' => '0000',
                    'time_hi_and_version' => '0000',
                    'clock_seq_hi_and_reserved' => '00',
                    'clock_seq_low' => '00',
                    'node' => '000000000000',
                ],
                'urn' => 'urn:uuid:00000000-0000-0000-0000-000000000000',
                'time' => '0',
                'clockSeq' => '0000',
                // This is a departure from the Python tests. The Python tests
                // are technically "correct" because all bits are set to zero,
                // so it stands to reason that the variant is also zero, but
                // that leads to this being considered a "Reserved NCS" variant,
                // and that is not the case. RFC 4122 defines this special UUID,
                // so it is an RFC 4122 variant.
                'variant' => Uuid::RFC_4122,
                'version' => null,
            ],
            [
                'string' => '00010203-0405-0607-0809-0a0b0c0d0e0f',
                'hex' => '000102030405060708090a0b0c0d0e0f',
                'bytes' => 'AAECAwQFBgcICQoLDA0ODw==',
                'int' => '5233100606242806050955395731361295',
                'fields' => [
                    'time_low' => '00010203',
                    'time_mid' => '0405',
                    'time_hi_and_version' => '0607',
                    'clock_seq_hi_and_reserved' => '08',
                    'clock_seq_low' => '09',
                    'node' => '0a0b0c0d0e0f',
                ],
                'urn' => 'urn:uuid:00010203-0405-0607-0809-0a0b0c0d0e0f',
                'time' => '607040500010203',
                'clockSeq' => '0809',
                'variant' => Uuid::RESERVED_NCS,
                'version' => null,
            ],
            [
                'string' => '02d9e6d5-9467-382e-8f9b-9300a64ac3cd',
                'hex' => '02d9e6d59467382e8f9b9300a64ac3cd',
                'bytes' => 'Atnm1ZRnOC6Pm5MApkrDzQ==',
                'int' => '3789866285607910888100818383505376205',
                'fields' => [
                    'time_low' => '02d9e6d5',
                    'time_mid' => '9467',
                    'time_hi_and_version' => '382e',
                    'clock_seq_hi_and_reserved' => '8f',
                    'clock_seq_low' => '9b',
                    'node' => '9300a64ac3cd',
                ],
                'urn' => 'urn:uuid:02d9e6d5-9467-382e-8f9b-9300a64ac3cd',
                'time' => '82e946702d9e6d5',
                'clockSeq' => '0f9b',
                'variant' => Uuid::RFC_4122,
                'version' => Uuid::UUID_TYPE_HASH_MD5,
            ],
            [
                'string' => '12345678-1234-5678-1234-567812345678',
                'hex' => '12345678123456781234567812345678',
                'bytes' => 'EjRWeBI0VngSNFZ4EjRWeA==',
                'int' => '24197857161011715162171839636988778104',
                'fields' => [
                    'time_low' => '12345678',
                    'time_mid' => '1234',
                    'time_hi_and_version' => '5678',
                    'clock_seq_hi_and_reserved' => '12',
                    'clock_seq_low' => '34',
                    'node' => '567812345678',
                ],
                'urn' => 'urn:uuid:12345678-1234-5678-1234-567812345678',
                'time' => '678123412345678',
                'clockSeq' => '1234',
                'variant' => Uuid::RESERVED_NCS,
                'version' => null,
            ],
            [
                'string' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'hex' => '6ba7b8109dad11d180b400c04fd430c8',
                'bytes' => 'a6e4EJ2tEdGAtADAT9QwyA==',
                'int' => '143098242404177361603877621312831893704',
                'fields' => [
                    'time_low' => '6ba7b810',
                    'time_mid' => '9dad',
                    'time_hi_and_version' => '11d1',
                    'clock_seq_hi_and_reserved' => '80',
                    'clock_seq_low' => 'b4',
                    'node' => '00c04fd430c8',
                ],
                'urn' => 'urn:uuid:6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'time' => '1d19dad6ba7b810',
                'clockSeq' => '00b4',
                'variant' => Uuid::RFC_4122,
                'version' => Uuid::UUID_TYPE_TIME,
            ],
            [
                'string' => '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
                'hex' => '6ba7b8119dad11d180b400c04fd430c8',
                'bytes' => 'a6e4EZ2tEdGAtADAT9QwyA==',
                'int' => '143098242483405524118141958906375844040',
                'fields' => [
                    'time_low' => '6ba7b811',
                    'time_mid' => '9dad',
                    'time_hi_and_version' => '11d1',
                    'clock_seq_hi_and_reserved' => '80',
                    'clock_seq_low' => 'b4',
                    'node' => '00c04fd430c8',
                ],
                'urn' => 'urn:uuid:6ba7b811-9dad-11d1-80b4-00c04fd430c8',
                'time' => '1d19dad6ba7b811',
                'clockSeq' => '00b4',
                'variant' => Uuid::RFC_4122,
                'version' => Uuid::UUID_TYPE_TIME,
            ],
            [
                'string' => '6ba7b812-9dad-11d1-80b4-00c04fd430c8',
                'hex' => '6ba7b8129dad11d180b400c04fd430c8',
                'bytes' => 'a6e4Ep2tEdGAtADAT9QwyA==',
                'int' => '143098242562633686632406296499919794376',
                'fields' => [
                    'time_low' => '6ba7b812',
                    'time_mid' => '9dad',
                    'time_hi_and_version' => '11d1',
                    'clock_seq_hi_and_reserved' => '80',
                    'clock_seq_low' => 'b4',
                    'node' => '00c04fd430c8',
                ],
                'urn' => 'urn:uuid:6ba7b812-9dad-11d1-80b4-00c04fd430c8',
                'time' => '1d19dad6ba7b812',
                'clockSeq' => '00b4',
                'variant' => Uuid::RFC_4122,
                'version' => Uuid::UUID_TYPE_TIME,
            ],
            [
                'string' => '6ba7b814-9dad-11d1-80b4-00c04fd430c8',
                'hex' => '6ba7b8149dad11d180b400c04fd430c8',
                'bytes' => 'a6e4FJ2tEdGAtADAT9QwyA==',
                'int' => '143098242721090011660934971687007695048',
                'fields' => [
                    'time_low' => '6ba7b814',
                    'time_mid' => '9dad',
                    'time_hi_and_version' => '11d1',
                    'clock_seq_hi_and_reserved' => '80',
                    'clock_seq_low' => 'b4',
                    'node' => '00c04fd430c8',
                ],
                'urn' => 'urn:uuid:6ba7b814-9dad-11d1-80b4-00c04fd430c8',
                'time' => '1d19dad6ba7b814',
                'clockSeq' => '00b4',
                'variant' => Uuid::RFC_4122,
                'version' => Uuid::UUID_TYPE_TIME,
            ],
            [
                'string' => '7d444840-9dc0-11d1-b245-5ffdce74fad2',
                'hex' => '7d4448409dc011d1b2455ffdce74fad2',
                'bytes' => 'fURIQJ3AEdGyRV/9znT60g==',
                'int' => '166508041112410060672666770310773930706',
                'fields' => [
                    'time_low' => '7d444840',
                    'time_mid' => '9dc0',
                    'time_hi_and_version' => '11d1',
                    'clock_seq_hi_and_reserved' => 'b2',
                    'clock_seq_low' => '45',
                    'node' => '5ffdce74fad2',
                ],
                'urn' => 'urn:uuid:7d444840-9dc0-11d1-b245-5ffdce74fad2',
                'time' => '1d19dc07d444840',
                'clockSeq' => '3245',
                'variant' => Uuid::RFC_4122,
                'version' => Uuid::UUID_TYPE_TIME,
            ],
            [
                'string' => 'e902893a-9d22-3c7e-a7b8-d6e313b71d9f',
                'hex' => 'e902893a9d223c7ea7b8d6e313b71d9f',
                'bytes' => '6QKJOp0iPH6nuNbjE7cdnw==',
                'int' => '309723290945582129846206211755626405279',
                'fields' => [
                    'time_low' => 'e902893a',
                    'time_mid' => '9d22',
                    'time_hi_and_version' => '3c7e',
                    'clock_seq_hi_and_reserved' => 'a7',
                    'clock_seq_low' => 'b8',
                    'node' => 'd6e313b71d9f',
                ],
                'urn' => 'urn:uuid:e902893a-9d22-3c7e-a7b8-d6e313b71d9f',
                'time' => 'c7e9d22e902893a',
                'clockSeq' => '27b8',
                'variant' => Uuid::RFC_4122,
                'version' => Uuid::UUID_TYPE_HASH_MD5,
            ],
            [
                'string' => 'eb424026-6f54-4ef8-a4d0-bb658a1fc6cf',
                'hex' => 'eb4240266f544ef8a4d0bb658a1fc6cf',
                'bytes' => '60JAJm9UTvik0Ltlih/Gzw==',
                'int' => '312712571721458096795100956955942831823',
                'fields' => [
                    'time_low' => 'eb424026',
                    'time_mid' => '6f54',
                    'time_hi_and_version' => '4ef8',
                    'clock_seq_hi_and_reserved' => 'a4',
                    'clock_seq_low' => 'd0',
                    'node' => 'bb658a1fc6cf',
                ],
                'urn' => 'urn:uuid:eb424026-6f54-4ef8-a4d0-bb658a1fc6cf',
                'time' => 'ef86f54eb424026',
                'clockSeq' => '24d0',
                'variant' => Uuid::RFC_4122,
                'version' => Uuid::UUID_TYPE_RANDOM,
            ],
            [
                'string' => 'f81d4fae-7dec-11d0-a765-00a0c91e6bf6',
                'hex' => 'f81d4fae7dec11d0a76500a0c91e6bf6',
                'bytes' => '+B1Prn3sEdCnZQCgyR5r9g==',
                'int' => '329800735698586629295641978511506172918',
                'fields' => [
                    'time_low' => 'f81d4fae',
                    'time_mid' => '7dec',
                    'time_hi_and_version' => '11d0',
                    'clock_seq_hi_and_reserved' => 'a7',
                    'clock_seq_low' => '65',
                    'node' => '00a0c91e6bf6',
                ],
                'urn' => 'urn:uuid:f81d4fae-7dec-11d0-a765-00a0c91e6bf6',
                'time' => '1d07decf81d4fae',
                'clockSeq' => '2765',
                'variant' => Uuid::RFC_4122,
                'version' => Uuid::UUID_TYPE_TIME,
            ],
            [
                'string' => 'fffefdfc-fffe-fffe-fffe-fffefdfcfbfa',
                'hex' => 'fffefdfcfffefffefffefffefdfcfbfa',
                'bytes' => '//79/P/+//7//v/+/fz7+g==',
                'int' => '340277133821575024845345576078114880506',
                'fields' => [
                    'time_low' => 'fffefdfc',
                    'time_mid' => 'fffe',
                    'time_hi_and_version' => 'fffe',
                    'clock_seq_hi_and_reserved' => 'ff',
                    'clock_seq_low' => 'fe',
                    'node' => 'fffefdfcfbfa',
                ],
                'urn' => 'urn:uuid:fffefdfc-fffe-fffe-fffe-fffefdfcfbfa',
                'time' => 'ffefffefffefdfc',
                'clockSeq' => '3ffe',
                'variant' => Uuid::RESERVED_FUTURE,
                'version' => null,
            ],
            [
                'string' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'hex' => 'ffffffffffffffffffffffffffffffff',
                'bytes' => '/////////////////////w==',
                'int' => '340282366920938463463374607431768211455',
                'fields' => [
                    'time_low' => 'ffffffff',
                    'time_mid' => 'ffff',
                    'time_hi_and_version' => 'ffff',
                    'clock_seq_hi_and_reserved' => 'ff',
                    'clock_seq_low' => 'ff',
                    'node' => 'ffffffffffff',
                ],
                'urn' => 'urn:uuid:ffffffff-ffff-ffff-ffff-ffffffffffff',
                'time' => 'fffffffffffffff',
                // This is a departure from the Python tests. The Python tests
                // are technically "correct" because all bits are set to one,
                // which ends up calculating the variant as 7, or "Reserved
                // Future," but that is not the case, and now that max UUIDs
                // are defined as a special type, within the RFC 4122 variant
                // rules, we also consider it an RFC 4122 variant.
                //
                // Similarly, Python's tests think the clock sequence should be
                // 0x3fff because of the bit shifting performed on this field.
                // However, since all the bits in this UUID are defined as being
                // set to one, we will consider the clock sequence as 0xffff,
                // which all bits set to one.
                'clockSeq' => 'ffff',
                'variant' => Uuid::RFC_4122,
                'version' => null,
            ],
        ];
    }

    public function testStringUuidJsonSerialize(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid1()->toString());

        /** @noinspection PhpUnitAnnotationToAttributeInspection */
        /** @uses FastStringUuid::jsonSerialize */
        $this->assertSame('"' . $uuid->toString() . '"', json_encode($uuid, flags: JSON_THROW_ON_ERROR));
    }

    public function testLazyStringUuidJsonSerialize(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid1()->toString());

        /** @noinspection PhpUnitAnnotationToAttributeInspection */
        /** @uses FastLazyUuidFromString::jsonSerialize */
        $this->assertSame('"' . $uuid->toString() . '"', json_encode($uuid, flags: JSON_THROW_ON_ERROR));
    }

    public function testBytesUuidJsonSerialize(): void
    {
        $uuid = Uuid::uuid1();

        /** @noinspection PhpUnitAnnotationToAttributeInspection */
        /** @uses FastBytesUuid::jsonSerialize */
        $this->assertSame('"' . $uuid->toString() . '"', json_encode($uuid, flags: JSON_THROW_ON_ERROR));
    }

    public function testStringUuidSerialize(): void
    {
        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $serialized = serialize($uuid);

        /** @var UuidInterface $unserializedUuid */
        $unserializedUuid = unserialize($serialized);

        $this->assertTrue($uuid->equals($unserializedUuid));
    }

    public function testLazyStringUuidSerialize(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString('ff6f8cb0-c57d-11e1-9b21-0800200c9a66');
        $serialized = serialize($uuid);

        /** @var UuidInterface $unserializedUuid */
        $unserializedUuid = unserialize($serialized);

        $this->assertTrue($uuid->equals($unserializedUuid));
    }

    public function testBytesUuidSerialize(): void
    {
        $uuid = Uuid::uuid4();
        $serialized = serialize($uuid);

        /** @var UuidInterface $unserializedUuid */
        $unserializedUuid = unserialize($serialized);

        $this->assertTrue($uuid->equals($unserializedUuid));
    }

    public function testStringUuidSerializeWithOldStringFormat(): void
    {
        $serialized = 'C:36:"Mougrim\FastUuid\Uuid\FastStringUuid":36:{b3cd586a-e3ca-44f3-988c-f4d666c1bf4d}';

        /** @var UuidInterface $unserializedUuid */
        $unserializedUuid = unserialize($serialized);

        $this->assertSame('b3cd586a-e3ca-44f3-988c-f4d666c1bf4d', $unserializedUuid->toString());
    }

    public function testBytesUuidSerializeWithOldStringFormat(): void
    {
        $serialized = 'C:35:"Mougrim\FastUuid\Uuid\FastBytesUuid":16:{' . base64_decode('s81YauPKRPOYjPTWZsG/TQ==') . '}';

        /** @var UuidInterface $unserializedUuid */
        $unserializedUuid = unserialize($serialized);

        $this->assertSame('b3cd586a-e3ca-44f3-988c-f4d666c1bf4d', $unserializedUuid->toString());
    }

    public function testUuid3WithEmptyNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID string:');

        Uuid::uuid3('', '');
    }

    public function testStringUuidUuid3WithEmptyName(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid3(Uuid::NIL, '')->toString());

        $this->assertSame('4ae71336-e44b-39bf-b9d2-752e234818a5', $uuid->toString());
    }

    public function testLazyStringUuidUuid3WithEmptyName(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid3(Uuid::NIL, '')->toString());

        $this->assertSame('4ae71336-e44b-39bf-b9d2-752e234818a5', $uuid->toString());
    }

    public function testBytesUuidUuid3WithEmptyName(): void
    {
        $uuid = Uuid::uuid3(Uuid::NIL, '');

        $this->assertSame('4ae71336-e44b-39bf-b9d2-752e234818a5', $uuid->toString());
    }

    public function testStringUuidUuid3WithZeroName(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid3(Uuid::NIL, '0')->toString());

        $this->assertSame('19826852-5007-3022-a72a-212f66e9fac3', $uuid->toString());
    }

    public function testLazyStringUuidUuid3WithZeroName(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid3(Uuid::NIL, '0')->toString());

        $this->assertSame('19826852-5007-3022-a72a-212f66e9fac3', $uuid->toString());
    }

    public function testBytesUuidUuid3WithZeroName(): void
    {
        $uuid = Uuid::uuid3(Uuid::NIL, '0');

        $this->assertSame('19826852-5007-3022-a72a-212f66e9fac3', $uuid->toString());
    }

    public function testUuid5WithEmptyNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID string:');

        Uuid::uuid5('', '');
    }

    public function testStringUuidUuid5WithEmptyName(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid5(Uuid::NIL, '')->toString());

        $this->assertSame('e129f27c-5103-5c5c-844b-cdf0a15e160d', $uuid->toString());
    }

    public function testLazyStringUuidUuid5WithEmptyName(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid5(Uuid::NIL, '')->toString());

        $this->assertSame('e129f27c-5103-5c5c-844b-cdf0a15e160d', $uuid->toString());
    }

    public function testBytesUuidUuid5WithEmptyName(): void
    {
        $uuid = Uuid::uuid5(Uuid::NIL, '');

        $this->assertSame('e129f27c-5103-5c5c-844b-cdf0a15e160d', $uuid->toString());
    }

    public function testStringUuidUuid5WithZeroName(): void
    {
        $uuid = Uuid::fromString(Uuid::uuid5(Uuid::NIL, '0')->toString());

        $this->assertSame('b6c54489-38a0-5f50-a60a-fd8d76219cae', $uuid->toString());
    }

    public function testLazyStringUuidUuid5WithZeroName(): void
    {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $uuid = Uuid::fromString(Uuid::uuid5(Uuid::NIL, '0')->toString());

        $this->assertSame('b6c54489-38a0-5f50-a60a-fd8d76219cae', $uuid->toString());
    }

    public function testBytesUuidUuid5WithZeroName(): void
    {
        $uuid = Uuid::uuid5(Uuid::NIL, '0');

        $this->assertSame('b6c54489-38a0-5f50-a60a-fd8d76219cae', $uuid->toString());
    }

    public function testStringUuidGetDateTimeThrowsExceptionWhenDateTimeCannotParseDate(): void
    {
        $numberConverter = new BigNumberConverter();
        $timeConverter = $this->createMock(TimeConverterInterface::class);

        $timeConverter->expects($this->once())
            ->method('convertTime')
            ->willReturn(new Time(1234567890, '1234567'));

        $builder = new DefaultUuidBuilder($numberConverter, $timeConverter);
        $codec = new StringCodec($builder);

        $factory = (new FastUuidFactoryFactory())->createAndSetFactory(timeConverter: $timeConverter, codec: $codec);

        $uuid = $factory->fromString('b1484596-25dc-11ea-978f-2e728ce88125');

        $this->expectException(DateTimeException::class);
        $this->expectExceptionMessage(
            'Failed to parse time string (@1234567890.1234567) at position 18 (7): Unexpected character'
        );

        $uuid->getDateTime();
    }

    public function testLazyStringUuidGetDateTimeThrowsExceptionWhenDateTimeCannotParseDate(): void
    {
        $numberConverter = new BigNumberConverter();
        $timeConverter = $this->createMock(TimeConverterInterface::class);

        $timeConverter->expects($this->once())
            ->method('convertTime')
            ->willReturn(new Time(1234567890, '1234567'));

        $builder = new DefaultUuidBuilder($numberConverter, $timeConverter);
        $codec = new StringCodec($builder);

        $factory = (new FastUuidFactoryFactory())
            ->createAndSetFactory(useLazy: true, timeConverter: $timeConverter, codec: $codec);

        $uuid = $factory->fromString('b1484596-25dc-11ea-978f-2e728ce88125');

        $this->expectException(DateTimeException::class);
        $this->expectExceptionMessage(
            'Failed to parse time string (@1234567890.1234567) at position 18 (7): Unexpected character'
        );

        $uuid->getDateTime();
    }

    public function testBytesUuidGetDateTimeThrowsExceptionWhenDateTimeCannotParseDate(): void
    {
        $numberConverter = new BigNumberConverter();
        $timeConverter = $this->createMock(TimeConverterInterface::class);

        $timeConverter->expects($this->once())
            ->method('convertTime')
            ->willReturn(new Time(1234567890, '1234567'));

        $builder = new DefaultUuidBuilder($numberConverter, $timeConverter);
        $codec = new StringCodec($builder);

        $factory = (new FastUuidFactoryFactory())->createAndSetFactory(timeConverter: $timeConverter, codec: $codec);

        $uuid = $factory->fromBytes($factory->fromString('b1484596-25dc-11ea-978f-2e728ce88125')->getBytes());

        $this->expectException(DateTimeException::class);
        $this->expectExceptionMessage(
            'Failed to parse time string (@1234567890.1234567) at position 18 (7): Unexpected character'
        );

        $uuid->getDateTime();
    }

    #[DataProvider('provideStaticCreationMethodsReturnSpecificUuidInstances')]
    public function testStaticCreationMethodsReturnSpecificUuidInstances(
        string $staticMethod,
        array $args,
        string $expectedInstanceOf,
    ): void {
        $this->assertInstanceOf($expectedInstanceOf, Uuid::$staticMethod(...$args));
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function provideStaticCreationMethodsReturnSpecificUuidInstances(): array
    {
        return [
            'uuid1' => [
                'staticMethod' => 'uuid1',
                'args' => [],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'uuid2' => [
                'staticMethod' => 'uuid2',
                'args' => [Uuid::DCE_DOMAIN_PERSON],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'uuid3' => [
                'staticMethod' => 'uuid3',
                'args' => [Uuid::NIL, 'foobar'],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'uuid4' => [
                'staticMethod' => 'uuid4',
                'args' => [],
                 'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'uuid5' => [
                'staticMethod' => 'uuid5',
                'args' => [Uuid::NIL, 'foobar'],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'uuid6' => [
                'staticMethod' => 'uuid6',
                'args' => [],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'uuid7' => [
                'staticMethod' => 'uuid7',
                'args' => [],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'uuid8' => [
                'staticMethod' => 'uuid8',
                'args' => ["\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff"],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'fromBytes' => [
                'staticMethod' => 'fromBytes',
                'args' => [base64_decode('AAAAAAAAAAAAAAAAAAAAAA==')],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'fromString' => [
                'staticMethod' => 'fromString',
                'args' => ['ff6f8cb0-c57d-11e1-9b21-0800200c9a66'],
                'expectedInstanceOf' => FastStringUuid::class,
            ],
            'fromDateTime' => [
                'staticMethod' => 'fromDateTime',
                'args' => [new DateTimeImmutable()],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'fromHexadecimal' => [
                'staticMethod' => 'fromHexadecimal',
                'args' => [new Hexadecimal('0x1EA78DEB37CE625E8F1A025041000001')],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
            'fromInteger' => [
                'staticMethod' => 'fromInteger',
                'args' => ['10'],
                'expectedInstanceOf' => FastBytesUuid::class,
            ],
        ];
    }

    #[DataProvider('provideLazyStaticCreationMethodsReturnSpecificUuidInstances')]
    public function testLazyStaticCreationMethodsReturnSpecificUuidInstances(
        string $staticMethod,
        array $args,
        string $expectedInstanceOf,
    ): void {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $this->assertInstanceOf($expectedInstanceOf, Uuid::$staticMethod(...$args));
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function provideLazyStaticCreationMethodsReturnSpecificUuidInstances(): array
    {
        return [
            'uuid1' => [
                'staticMethod' => 'uuid1',
                'args' => [],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'uuid2' => [
                'staticMethod' => 'uuid2',
                'args' => [Uuid::DCE_DOMAIN_PERSON],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'uuid3' => [
                'staticMethod' => 'uuid3',
                'args' => [Uuid::NIL, 'foobar'],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'uuid4' => [
                'staticMethod' => 'uuid4',
                'args' => [],
                 'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'uuid5' => [
                'staticMethod' => 'uuid5',
                'args' => [Uuid::NIL, 'foobar'],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'uuid6' => [
                'staticMethod' => 'uuid6',
                'args' => [],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'uuid7' => [
                'staticMethod' => 'uuid7',
                'args' => [],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'uuid8' => [
                'staticMethod' => 'uuid8',
                'args' => ["\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff"],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'fromBytes' => [
                'staticMethod' => 'fromBytes',
                'args' => [base64_decode('AAAAAAAAAAAAAAAAAAAAAA==')],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'fromString' => [
                'staticMethod' => 'fromString',
                'args' => ['ff6f8cb0-c57d-11e1-9b21-0800200c9a66'],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'fromDateTime' => [
                'staticMethod' => 'fromDateTime',
                'args' => [new DateTimeImmutable()],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'fromHexadecimal' => [
                'staticMethod' => 'fromHexadecimal',
                'args' => [new Hexadecimal('0x1EA78DEB37CE625E8F1A025041000001')],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
            'fromInteger' => [
                'staticMethod' => 'fromInteger',
                'args' => ['10'],
                'expectedInstanceOf' => FastLazyUuidFromString::class,
            ],
        ];
    }

    #[DataProvider('provideStaticMethods')]
    public function testUuidInstancesBuiltFromStringAreEquivalentToTheirGeneratedCounterparts(
        string $staticMethod,
        array $args = [],
    ): void {
        $generated = Uuid::$staticMethod(...$args);

        self::assertSame(
            (string) $generated,
            (string) Uuid::fromString($generated->toString())
        );
    }

    #[DataProvider('provideStaticMethods')]
    public function testLazyUuidInstancesBuiltFromStringAreEquivalentToTheirGeneratedCounterparts(
        string $staticMethod,
        array $args = [],
    ): void {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $generated = Uuid::$staticMethod(...$args);

        self::assertSame(
            (string) $generated,
            (string) Uuid::fromString($generated->toString())
        );
    }

    #[DataProvider('provideStaticMethods')]
    public function testUuidInstancesBuiltFromBytesAreEquivalentToTheirGeneratedCounterparts(
        string $staticMethod,
        array $args = [],
    ): void {
        $generated = Uuid::$staticMethod(...$args);

        self::assertSame(
            (string) $generated,
            (string) Uuid::fromBytes($generated->getBytes())
        );
    }

    #[DataProvider('provideStaticMethods')]
    public function testLazyUuidInstancesBuiltFromBytesAreEquivalentToTheirGeneratedCounterparts(
        string $staticMethod,
        array $args = [],
    ): void {
        (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);

        $generated = Uuid::$staticMethod(...$args);

        self::assertSame(
            (string) $generated,
            (string) Uuid::fromBytes($generated->getBytes())
        );
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public static function provideStaticMethods(): array
    {
        return [
            'uuid1' => [
                'staticMethod' => 'uuid1',
                'args' => [],
            ],
            'uuid2' => [
                'staticMethod' => 'uuid2',
                'args' => [Uuid::DCE_DOMAIN_PERSON],
            ],
            'uuid3' => [
                'staticMethod' => 'uuid3',
                'args' => [Uuid::NIL, 'foobar'],
            ],
            'uuid4' => [
                'staticMethod' => 'uuid4',
                'args' => [],
            ],
            'uuid5' => [
                'staticMethod' => 'uuid5',
                'args' => [Uuid::NIL, 'foobar'],
            ],
            'uuid6' => [
                'staticMethod' => 'uuid6',
                'args' => [],
            ],
            'uuid7' => [
                'staticMethod' => 'uuid7',
                'args' => [],
            ],
            'uuid8' => [
                'staticMethod' => 'uuid8',
                'args' => ["\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff"],
            ],
            'fromBytes' => [
                'staticMethod' => 'fromBytes',
                'args' => [base64_decode('AAAAAAAAAAAAAAAAAAAAAA==')],
            ],
            'fromString' => [
                'staticMethod' => 'fromString',
                'args' => ['ff6f8cb0-c57d-11e1-9b21-0800200c9a66'],
            ],
            'fromDateTime' => [
                'staticMethod' => 'fromDateTime',
                'args' => [new DateTimeImmutable()],
            ],
            'fromHexadecimal' => [
                'staticMethod' => 'fromHexadecimal',
                'args' => [new Hexadecimal('0x1EA78DEB37CE625E8F1A025041000001')],
            ],
            'fromInteger' => [
                'staticMethod' => 'fromInteger',
                'args' => ['10'],
            ],
        ];
    }
}
