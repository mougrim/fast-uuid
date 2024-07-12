<?php
declare(strict_types=1);

namespace Mougrim\FastUuid\Factory;

use Ramsey\Uuid\Builder\UuidBuilderInterface;
use Ramsey\Uuid\Codec\CodecInterface;
use Ramsey\Uuid\Codec\GuidStringCodec;
use Ramsey\Uuid\Converter\NumberConverterInterface;
use Ramsey\Uuid\Converter\Time\UnixTimeConverter;
use Ramsey\Uuid\Converter\TimeConverterInterface;
use Ramsey\Uuid\Exception\InvalidArgumentException;
use Ramsey\Uuid\FeatureSet;
use Ramsey\Uuid\Generator\RandomGeneratorInterface;
use Ramsey\Uuid\Generator\TimeGeneratorInterface;
use Ramsey\Uuid\Guid\GuidBuilder;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;

/**
 * @author Mougrim <for-open-source@mougrim.io>
 */
class FastUuidFactoryFactory
{
    private static FastUuidFactory $fastUuidFactory;

    /**
     * @internal only for FastLazyUuidFromString unwrap/unserialize
     */
    public static function getFastUuidFactory(): FastUuidFactory
    {
        if (!isset(self::$fastUuidFactory)) {
            self::$fastUuidFactory = (new self())->create();
        }
        return self::$fastUuidFactory;
    }

    /**
     * @internal only for testing purposes
     */
    public static function setFastUuidFactory(FastUuidFactory $fastUuidFactory): void
    {
        self::$fastUuidFactory = $fastUuidFactory;
    }

    public function create(
        ?FeatureSet $features = null,
        ?UuidBuilderInterface $uuidBuilder = null,
        ?TimeConverterInterface $timeConverter = null,
        ?TimeConverterInterface $unixTimeConverter = null,
        ?NumberConverterInterface $numberConverter = null,
        ?RandomGeneratorInterface $randomGenerator = null,
        ?TimeGeneratorInterface $timeGenerator = null,
        ?CodecInterface $codec = null,
    ): FastUuidFactory|FastLazyUuidFactory {
        return $this->doCreate(
            features: $features,
            uuidBuilder: $uuidBuilder,
            timeConverter: $timeConverter,
            unixTimeConverter: $unixTimeConverter,
            numberConverter: $numberConverter,
            randomGenerator: $randomGenerator,
            timeGenerator: $timeGenerator,
            codec: $codec,
        );
    }

    /**
     * @template T of bool
     * @param T $useLazy
     * @return (T is false ? FastUuidFactory : FastLazyUuidFactory)
     */
    public function createAndSetFactory(
        ?FeatureSet $features = null,
        bool $useLazy = false,
        ?UuidBuilderInterface $uuidBuilder = null,
        ?TimeConverterInterface $timeConverter = null,
        ?TimeConverterInterface $unixTimeConverter = null,
        ?NumberConverterInterface $numberConverter = null,
        ?RandomGeneratorInterface $randomGenerator = null,
        ?TimeGeneratorInterface $timeGenerator = null,
        ?CodecInterface $codec = null,
    ): FastUuidFactory|FastLazyUuidFactory {
        $factory = $this->doCreate(
            features: $features,
            useLazy: $useLazy,
            uuidBuilder: $uuidBuilder,
            timeConverter: $timeConverter,
            unixTimeConverter: $unixTimeConverter,
            numberConverter: $numberConverter,
            randomGenerator: $randomGenerator,
            timeGenerator: $timeGenerator,
            codec: $codec,
            setFastFactory: true,
        );

        Uuid::setFactory($factory);

        return $factory;
    }

    private function doCreate(
        ?FeatureSet $features = null,
        bool $useLazy = false,
        ?UuidBuilderInterface $uuidBuilder = null,
        ?TimeConverterInterface $timeConverter = null,
        ?TimeConverterInterface $unixTimeConverter = null,
        ?NumberConverterInterface $numberConverter = null,
        ?RandomGeneratorInterface $randomGenerator = null,
        ?TimeGeneratorInterface $timeGenerator = null,
        ?CodecInterface $codec = null,
        bool $setFastFactory = false,
    ): FastUuidFactory|FastLazyUuidFactory {
        $features = $features ?: new FeatureSet();

        if ($features->getBuilder() instanceof GuidBuilder) {
            throw new InvalidArgumentException('Guid is not supported by FastUuid yet');
        }
        if ($codec instanceof GuidStringCodec || $features->getCodec() instanceof GuidStringCodec) {
            throw new InvalidArgumentException('Guid is not supported by FastUuid yet');
        }

        $uuidFactory = new UuidFactory($features);
        if ($uuidBuilder) {
            $uuidFactory->setUuidBuilder($uuidBuilder);
        }
        if ($numberConverter) {
            $uuidFactory->setNumberConverter($numberConverter);
        }
        if ($randomGenerator) {
            $uuidFactory->setRandomGenerator($randomGenerator);
        }
        if ($timeGenerator) {
            $uuidFactory->setTimeGenerator($timeGenerator);
        }
        if ($codec) {
            $uuidFactory->setCodec($codec);
        }

        $fastUuidFactory = new FastUuidFactory(
            $uuidFactory,
            $timeConverter ?? $features->getTimeConverter(),
            $unixTimeConverter ?? new UnixTimeConverter($features->getCalculator()),
        );

        if ($setFastFactory) {
            self::$fastUuidFactory = $fastUuidFactory;
        }

        if ($useLazy) {
            return new FastLazyUuidFactory($uuidFactory, $fastUuidFactory);
        }

        return $fastUuidFactory;
    }
}
