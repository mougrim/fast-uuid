<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Mougrim\FastUuid\Benchmark;

use DateTimeImmutable;
use Mougrim\FastUuid\Factory\FastUuidFactoryFactory;
use Ramsey\Uuid\Provider\Node\StaticNodeProvider;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer as IntegerIdentifier;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactory;
use Ramsey\Uuid\UuidFactoryInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * Based on https://github.com/ramsey/uuid/blob/4.7.6/tests/benchmark/UuidGenerationBench.php
 */
final class UuidGenerationBench
{
    private Hexadecimal $node;
    private int $clockSequence;
    private IntegerIdentifier $localIdentifier;
    private UuidInterface $namespace;
    private UuidInterface $lazyNamespace;
    private UuidFactoryInterface $fastUuidFactory;
    private UuidFactoryInterface $fastLazyUuidFactory;
    private UuidInterface $fastStringNamespace;
    private UuidInterface $fastBytesNamespace;
    private UuidInterface $fastLazyStringNamespace;

    public function __construct()
    {
        $this->node = (new StaticNodeProvider(new Hexadecimal('121212121212')))->getNode();
        $this->clockSequence = 16383;
        $this->localIdentifier = new IntegerIdentifier(5);
        $this->namespace = Uuid::getFactory()->fromString('c485840e-9389-4548-a276-aeecd9730e50');
        $this->lazyNamespace  = Uuid::fromString('c485840e-9389-4548-a276-aeecd9730e50');
        $this->fastUuidFactory = (new FastUuidFactoryFactory())->create();
        $this->fastLazyUuidFactory = (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);
        $this->fastStringNamespace = $this->fastUuidFactory->fromString('c485840e-9389-4548-a276-aeecd9730e50');
        $this->fastBytesNamespace = $this->fastUuidFactory->fromBytes(
            $this->fastUuidFactory->fromString('c485840e-9389-4548-a276-aeecd9730e50')->getBytes(),
        );
        $this->fastLazyStringNamespace = $this->fastLazyUuidFactory->fromString('c485840e-9389-4548-a276-aeecd9730e50');
        // restore factory
        Uuid::setFactory(new UuidFactory());
    }

    public function benchUuid1GenerationWithoutParameters(): void
    {
        Uuid::uuid1();
    }

    public function benchFastUuid1GenerationWithoutParameters(): void
    {
        $this->fastUuidFactory->uuid1();
    }

    public function benchFastLazyUuid1GenerationWithoutParameters(): void
    {
        $this->fastLazyUuidFactory->uuid1();
    }

    public function benchUuid1GenerationWithNode(): void
    {
        Uuid::uuid1($this->node);
    }

    public function benchFastUuid1GenerationWithNode(): void
    {
        $this->fastUuidFactory->uuid1($this->node);
    }

    public function benchFastLazyUuid1GenerationWithNode(): void
    {
        $this->fastLazyUuidFactory->uuid1($this->node);
    }

    public function benchUuid1GenerationWithNodeAndClockSequence(): void
    {
        Uuid::uuid1($this->node, $this->clockSequence);
    }

    public function benchFastUuid1GenerationWithNodeAndClockSequence(): void
    {
        $this->fastUuidFactory->uuid1($this->node, $this->clockSequence);
    }

    public function benchFastLazyUuid1GenerationWithNodeAndClockSequence(): void
    {
        $this->fastLazyUuidFactory->uuid1($this->node, $this->clockSequence);
    }

    public function benchUuid2GenerationWithDomainAndLocalIdentifier(): void
    {
        Uuid::uuid2(Uuid::DCE_DOMAIN_ORG, $this->localIdentifier);
    }

    public function benchFastUuid2GenerationWithDomainAndLocalIdentifier(): void
    {
        $this->fastUuidFactory->uuid2(Uuid::DCE_DOMAIN_ORG, $this->localIdentifier);
    }

    public function benchFastLazyUuid2GenerationWithDomainAndLocalIdentifier(): void
    {
        $this->fastLazyUuidFactory->uuid2(Uuid::DCE_DOMAIN_ORG, $this->localIdentifier);
    }

    public function benchUuid2GenerationWithDomainAndLocalIdentifierAndNode(): void
    {
        Uuid::uuid2(Uuid::DCE_DOMAIN_ORG, $this->localIdentifier, $this->node);
    }

    public function benchFastUuid2GenerationWithDomainAndLocalIdentifierAndNode(): void
    {
        $this->fastUuidFactory->uuid2(Uuid::DCE_DOMAIN_ORG, $this->localIdentifier, $this->node);
    }

    public function benchFastLazyUuid2GenerationWithDomainAndLocalIdentifierAndNode(): void
    {
        $this->fastLazyUuidFactory->uuid2(Uuid::DCE_DOMAIN_ORG, $this->localIdentifier, $this->node);
    }

    public function benchUuid2GenerationWithDomainAndLocalIdentifierAndNodeAndClockSequence(): void
    {
        Uuid::uuid2(Uuid::DCE_DOMAIN_ORG, $this->localIdentifier, $this->node, 63);
    }

    public function benchFastUuid2GenerationWithDomainAndLocalIdentifierAndNodeAndClockSequence(): void
    {
        $this->fastUuidFactory->uuid2(Uuid::DCE_DOMAIN_ORG, $this->localIdentifier, $this->node, 63);
    }

    public function benchFastLazyUuid2GenerationWithDomainAndLocalIdentifierAndNodeAndClockSequence(): void
    {
        $this->fastLazyUuidFactory->uuid2(Uuid::DCE_DOMAIN_ORG, $this->localIdentifier, $this->node, 63);
    }

    public function benchUuid3Generation(): void
    {
        Uuid::uuid3($this->namespace, 'name');
    }

    public function benchLazyUuid3Generation(): void
    {
        Uuid::uuid3($this->lazyNamespace, 'name');
    }

    public function benchFastStringUuid3Generation(): void
    {
        $this->fastUuidFactory->uuid3($this->fastStringNamespace, 'name');
    }

    public function benchFastBytesUuid3Generation(): void
    {
        $this->fastUuidFactory->uuid3($this->fastBytesNamespace, 'name');
    }

    public function benchFastLazyUuid3Generation(): void
    {
        $this->fastLazyUuidFactory->uuid3($this->fastLazyStringNamespace, 'name');
    }

    public function benchUuid4Generation(): void
    {
        Uuid::uuid4();
    }

    public function benchFastUuid4Generation(): void
    {
        $this->fastUuidFactory->uuid4();
    }

    public function benchFastLazyUuid4Generation(): void
    {
        $this->fastLazyUuidFactory->uuid4();
    }

    public function benchUuid5Generation(): void
    {
        Uuid::uuid5($this->namespace, 'name');
    }

    public function benchLazyUuid5Generation(): void
    {
        Uuid::uuid5($this->lazyNamespace, 'name');
    }

    public function benchFastStringUuid5Generation(): void
    {
        $this->fastUuidFactory->uuid5($this->fastStringNamespace, 'name');
    }

    public function benchFastBytesUuid5Generation(): void
    {
        $this->fastUuidFactory->uuid5($this->fastBytesNamespace, 'name');
    }

    public function benchFastLazyUuid5Generation(): void
    {
        $this->fastLazyUuidFactory->uuid5($this->fastLazyStringNamespace, 'name');
    }

    public function benchUuid6GenerationWithoutParameters(): void
    {
        Uuid::uuid6();
    }

    public function benchFastUuid6GenerationWithoutParameters(): void
    {
        $this->fastUuidFactory->uuid6();
    }

    public function benchFastLazyUuid6GenerationWithoutParameters(): void
    {
        $this->fastLazyUuidFactory->uuid6();
    }

    public function benchUuid6GenerationWithNode(): void
    {
        Uuid::uuid6($this->node);
    }

    public function benchFastUuid6GenerationWithNode(): void
    {
        $this->fastUuidFactory->uuid6($this->node);
    }

    public function benchFastLazyUuid6GenerationWithNode(): void
    {
        $this->fastLazyUuidFactory->uuid6($this->node);
    }

    public function benchUuid6GenerationWithNodeAndClockSequence(): void
    {
        Uuid::uuid6($this->node, $this->clockSequence);
    }

    public function benchFastUuid6GenerationWithNodeAndClockSequence(): void
    {
        $this->fastUuidFactory->uuid6($this->node, $this->clockSequence);
    }

    public function benchFastLazyUuid6GenerationWithNodeAndClockSequence(): void
    {
        $this->fastLazyUuidFactory->uuid6($this->node, $this->clockSequence);
    }

    public function benchUuid7Generation(): void
    {
        Uuid::uuid7();
    }

    public function benchFastUuid7Generation(): void
    {
        $this->fastUuidFactory->uuid7();
    }

    public function benchFastLazyUuid7Generation(): void
    {
        $this->fastLazyUuidFactory->uuid7();
    }

    public function benchUuid7GenerationWithDateTime(): void
    {
        Uuid::uuid7(new DateTimeImmutable('@1663203901.667000'));
    }

    public function benchFastUuid7GenerationWithDateTime(): void
    {
        $this->fastUuidFactory->uuid7(new DateTimeImmutable('@1663203901.667000'));
    }

    public function benchFastLazyUuid7GenerationWithDateTime(): void
    {
        $this->fastLazyUuidFactory->uuid7(new DateTimeImmutable('@1663203901.667000'));
    }

    public function benchUuid8(): void
    {
        Uuid::uuid8("\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff");
    }

    public function benchFastUuid8(): void
    {
        $this->fastUuidFactory->uuid8("\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff");
    }

    public function benchFastLazyUuid8(): void
    {
        $this->fastLazyUuidFactory->uuid8("\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff");
    }
}
