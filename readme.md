<h1 style="text-align: center">mougrim/fast-uuid</h1>

<p style="text-align: center; font-weight: bold">
    A PHP library for generating and working with UUIDs faster than <a href="https://github.com/ramsey/uuid">ramsey/uuid</a> in many cases.
</p>

<p style="text-align: center">
    <a href="https://github.com/mougrim/fast-uuid"><img src="https://img.shields.io/badge/source-mougrim/fast-uuid-blue.svg?style=flat-square" alt="Source Code"></a>
    <a href="https://packagist.org/packages/mougrim/fast-uuid"><img src="https://img.shields.io/packagist/v/mougrim/fast-uuid.svg?style=flat-square&label=release" alt="Download Package"></a>
    <a href="https://php.net"><img src="https://img.shields.io/packagist/php-v/mougrim/fast-uuid.svg?style=flat-square&colorB=%238892BF" alt="PHP Programming Language"></a>
    <a href="https://github.com/mougrim/fast-uuid/blob/main/license.md"><img src="https://img.shields.io/packagist/l/mougrim/fast-uuid.svg?style=flat-square&colorB=darkcyan" alt="Read License"></a>
</p>

`mougrim/fast-uuid` is a PHP library for generating and working with universally unique identifiers (UUIDs).

`mougrim/fast-uuid` is based on the source code of <a href="https://github.com/ramsey/uuid">ramsey/uuid</a> and used it as a dependency, but it is faster in many cases.

## Installation

The preferred method of installation is via [Composer](https://getcomposer.org/). Run the following command to install the package and add it as a requirement to your project's `composer.json`:

```bash
composer require mougrim/fast-uuid
```

## Documentation

Creating the factory:
```php
use Mougrim\FastUuid\Factory\FastUuidFactoryFactory;
$factory = (new FastUuidFactoryFactory())->create();
```

If you want to use lazy initialization, create the factory in the following way:
```php
use Mougrim\FastUuid\Factory\FastUuidFactoryFactory;
$factory = (new FastUuidFactoryFactory())->createAndSetFactory(useLazy: true);
```


After creating the factory, you can use it as `ramsey/uuid`, for example, to generate a UUID v7:
```php
$factory->uuid7();
```

For more information, see the [ramsey/uuid documentation](https://github.com/ramsey/uuid/blob/4.x/README.md#documentation).

## Problem

`ramsey/uuid` is slow (sometimes very slow) when not using lazy initialization.

To use lazy initialization, you have to use static methods like `Uuid::fromString()`, `Uuid::uuid7()`, etc., with the default factory. If you use `Uuid::getFactory()` or change the default factory, you can't use lazy initialization.

So, if you want to use dependency injection (DI) or a non-default factory, you can't use the lazy initialization of `ramsey/uuid`.

`mougrim/fast-uuid` has a much faster non-lazy initialization than `ramsey/uuid`.

`mougrim/fast-uuid` has the same speed for lazy initialization as `ramsey/uuid`, but in cases where you need to unwrap a UUID (for example, to get the timestamp from it), `mougrim/fast-uuid` is much faster.

## Benchmark

To run the benchmark, use the following command:
```bash
vendor/bin/phpbench run  --report=aggregate --iterations=5 --revs=5000  --retry-threshold=2
```
The result:
```
+----------------------------+---------------------------------------------------------------------------------+-----+------+-----+-----------+-----------+--------+
| benchmark                  | subject                                                                         | set | revs | its | mem_peak  | mode      | rstdev |
+----------------------------+---------------------------------------------------------------------------------+-----+------+-----+-----------+-----------+--------+
| UuidStringConversionBench  | benchCreationOfTinyUuidFromString                                               |     | 5000 | 5   | 907.312kb | 3.740μs   | ±1.04% |
| UuidStringConversionBench  | benchLazyCreationOfTinyUuidFromString                                           |     | 5000 | 5   | 907.312kb | 0.222μs   | ±1.18% |
| UuidStringConversionBench  | benchFastCreationOfTinyUuidFromString                                           |     | 5000 | 5   | 907.312kb | 0.863μs   | ±0.97% |
| UuidStringConversionBench  | benchFastLazyCreationOfTinyUuidFromString                                       |     | 5000 | 5   | 907.408kb | 0.222μs   | ±0.96% |
| UuidStringConversionBench  | benchCreationOfHugeUuidFromString                                               |     | 5000 | 5   | 907.312kb | 1.555μs   | ±1.08% |
| UuidStringConversionBench  | benchLazyCreationOfHugeUuidFromString                                           |     | 5000 | 5   | 907.312kb | 0.216μs   | ±0.76% |
| UuidStringConversionBench  | benchFastCreationOfHugeUuidFromString                                           |     | 5000 | 5   | 907.312kb | 0.526μs   | ±1.41% |
| UuidStringConversionBench  | benchFastLazyCreationOfHugeUuidFromString                                       |     | 5000 | 5   | 907.408kb | 0.221μs   | ±0.94% |
| UuidStringConversionBench  | benchCreationOfUuidFromString                                                   |     | 5000 | 5   | 907.264kb | 3.524μs   | ±0.57% |
| UuidStringConversionBench  | benchLazyCreationOfUuidFromString                                               |     | 5000 | 5   | 907.312kb | 0.226μs   | ±1.04% |
| UuidStringConversionBench  | benchFastCreationOfUuidFromString                                               |     | 5000 | 5   | 907.312kb | 0.764μs   | ±0.54% |
| UuidStringConversionBench  | benchFastLazyCreationOfUuidFromString                                           |     | 5000 | 5   | 907.312kb | 0.231μs   | ±0.46% |
| UuidStringConversionBench  | benchCreationOfPromiscuousUuidsFromString                                       |     | 5000 | 5   | 907.408kb | 359.633μs | ±1.04% |
| UuidStringConversionBench  | benchLazyCreationOfPromiscuousUuidsFromString                                   |     | 5000 | 5   | 907.408kb | 22.120μs  | ±0.95% |
| UuidStringConversionBench  | benchFastCreationOfPromiscuousUuidsFromString                                   |     | 5000 | 5   | 907.408kb | 81.267μs  | ±0.78% |
| UuidStringConversionBench  | benchFastLazyCreationOfPromiscuousUuidsFromString                               |     | 5000 | 5   | 907.408kb | 22.868μs  | ±1.09% |
| UuidStringConversionBench  | benchCreationOfTinyUuidFromBytes                                                |     | 5000 | 5   | 907.312kb | 2.518μs   | ±0.28% |
| UuidStringConversionBench  | benchLazyCreationOfTinyUuidFromBytes                                            |     | 5000 | 5   | 907.312kb | 0.555μs   | ±1.43% |
| UuidStringConversionBench  | benchFastCreationOfTinyUuidFromBytes                                            |     | 5000 | 5   | 907.312kb | 0.686μs   | ±1.04% |
| UuidStringConversionBench  | benchFastLazyCreationOfTinyUuidFromBytes                                        |     | 5000 | 5   | 907.408kb | 0.529μs   | ±0.68% |
| UuidStringConversionBench  | benchCreationOfHugeUuidFromBytes                                                |     | 5000 | 5   | 907.312kb | 0.619μs   | ±1.12% |
| UuidStringConversionBench  | benchLazyCreationOfHugeUuidFromBytes                                            |     | 5000 | 5   | 907.312kb | 0.557μs   | ±1.26% |
| UuidStringConversionBench  | benchFastCreationOfHugeUuidFromBytes                                            |     | 5000 | 5   | 907.312kb | 0.400μs   | ±0.82% |
| UuidStringConversionBench  | benchFastLazyCreationOfHugeUuidFromBytes                                        |     | 5000 | 5   | 907.408kb | 0.534μs   | ±0.81% |
| UuidStringConversionBench  | benchCreationOfUuidFromBytes                                                    |     | 5000 | 5   | 907.264kb | 2.303μs   | ±1.05% |
| UuidStringConversionBench  | benchLazyCreationOfUuidFromBytes                                                |     | 5000 | 5   | 907.312kb | 0.544μs   | ±0.97% |
| UuidStringConversionBench  | benchFastCreationOfUuidFromBytes                                                |     | 5000 | 5   | 907.312kb | 0.764μs   | ±1.12% |
| UuidStringConversionBench  | benchFastLazyCreationOfUuidFromBytes                                            |     | 5000 | 5   | 907.312kb | 0.538μs   | ±1.28% |
| UuidStringConversionBench  | benchCreationOfPromiscuousUuidsFromBytes                                        |     | 5000 | 5   | 907.408kb | 247.696μs | ±0.99% |
| UuidStringConversionBench  | benchLazyCreationOfPromiscuousUuidsFromBytes                                    |     | 5000 | 5   | 907.408kb | 59.408μs  | ±0.97% |
| UuidStringConversionBench  | benchFastCreationOfPromiscuousUuidsFromBytes                                    |     | 5000 | 5   | 916.544kb | 82.147μs  | ±0.50% |
| UuidStringConversionBench  | benchFastLazyCreationOfPromiscuousUuidsFromBytes                                |     | 5000 | 5   | 907.408kb | 57.002μs  | ±0.94% |
| UuidStringConversionBench  | benchToStringConversionOfTinyUuid                                               |     | 5000 | 5   | 907.312kb | 0.464μs   | ±1.33% |
| UuidStringConversionBench  | benchLazyToStringConversionOfTinyUuid                                           |     | 5000 | 5   | 907.312kb | 0.034μs   | ±1.02% |
| UuidStringConversionBench  | benchFastStringToStringConversionOfTinyUuid                                     |     | 5000 | 5   | 907.408kb | 0.053μs   | ±0.24% |
| UuidStringConversionBench  | benchFastBytesToStringConversionOfTinyUuid                                      |     | 5000 | 5   | 907.408kb | 0.042μs   | ±1.13% |
| UuidStringConversionBench  | benchFastLazyToStringConversionOfTinyUuid                                       |     | 5000 | 5   | 907.408kb | 0.035μs   | ±1.44% |
| UuidStringConversionBench  | benchToStringConversionOfHugeUuid                                               |     | 5000 | 5   | 907.312kb | 0.467μs   | ±0.68% |
| UuidStringConversionBench  | benchLazyToStringConversionOfHugeUuid                                           |     | 5000 | 5   | 907.312kb | 0.033μs   | ±0.88% |
| UuidStringConversionBench  | benchFastStringToStringConversionOfHugeUuid                                     |     | 5000 | 5   | 907.408kb | 0.051μs   | ±0.52% |
| UuidStringConversionBench  | benchFastBytesToStringConversionOfHugeUuid                                      |     | 5000 | 5   | 907.408kb | 0.041μs   | ±0.48% |
| UuidStringConversionBench  | benchFastLazyToStringConversionOfHugeUuid                                       |     | 5000 | 5   | 907.408kb | 0.033μs   | ±0.90% |
| UuidStringConversionBench  | benchToStringConversionOfUuid                                                   |     | 5000 | 5   | 907.264kb | 0.466μs   | ±1.26% |
| UuidStringConversionBench  | benchLazyToStringConversionOfUuid                                               |     | 5000 | 5   | 907.312kb | 0.033μs   | ±1.16% |
| UuidStringConversionBench  | benchFastStringToStringConversionOfUuid                                         |     | 5000 | 5   | 907.312kb | 0.052μs   | ±1.18% |
| UuidStringConversionBench  | benchFastBytesToStringConversionOfUuid                                          |     | 5000 | 5   | 907.312kb | 0.041μs   | ±0.79% |
| UuidStringConversionBench  | benchFastLazyToStringConversionOfUuid                                           |     | 5000 | 5   | 907.312kb | 0.034μs   | ±0.69% |
| UuidStringConversionBench  | benchToStringConversionOfPromiscuousUuids                                       |     | 5000 | 5   | 907.408kb | 54.634μs  | ±0.98% |
| UuidStringConversionBench  | benchLazyToStringConversionOfPromiscuousUuids                                   |     | 5000 | 5   | 907.408kb | 5.154μs   | ±0.75% |
| UuidStringConversionBench  | benchFastStringToStringConversionOfPromiscuousUuids                             |     | 5000 | 5   | 907.408kb | 7.494μs   | ±0.45% |
| UuidStringConversionBench  | benchFastBytesToStringConversionOfPromiscuousUuids                              |     | 5000 | 5   | 907.408kb | 5.999μs   | ±0.96% |
| UuidStringConversionBench  | benchFastLazyToStringConversionOfPromiscuousUuids                               |     | 5000 | 5   | 907.408kb | 5.122μs   | ±0.70% |
| UuidStringConversionBench  | benchToBytesConversionOfTinyUuid                                                |     | 5000 | 5   | 907.312kb | 0.099μs   | ±1.32% |
| UuidStringConversionBench  | benchLazyToBytesConversionOfTinyUuid                                            |     | 5000 | 5   | 907.312kb | 0.170μs   | ±0.91% |
| UuidStringConversionBench  | benchFastStringToBytesConversionOfTinyUuid                                      |     | 5000 | 5   | 907.408kb | 0.057μs   | ±0.81% |
| UuidStringConversionBench  | benchFastBytesToBytesConversionOfTinyUuid                                       |     | 5000 | 5   | 907.408kb | 0.052μs   | ±0.93% |
| UuidStringConversionBench  | benchFastLazyToBytesConversionOfTinyUuid                                        |     | 5000 | 5   | 907.408kb | 0.040μs   | ±1.07% |
| UuidStringConversionBench  | benchToBytesConversionOfHugeUuid                                                |     | 5000 | 5   | 907.312kb | 0.095μs   | ±1.21% |
| UuidStringConversionBench  | benchLazyToBytesConversionOfHugeUuid                                            |     | 5000 | 5   | 907.312kb | 0.169μs   | ±0.63% |
| UuidStringConversionBench  | benchFastStringToBytesConversionOfHugeUuid                                      |     | 5000 | 5   | 907.408kb | 0.057μs   | ±0.84% |
| UuidStringConversionBench  | benchFastBytesToBytesConversionOfHugeUuid                                       |     | 5000 | 5   | 907.408kb | 0.053μs   | ±0.73% |
| UuidStringConversionBench  | benchFastLazyToBytesConversionOfHugeUuid                                        |     | 5000 | 5   | 907.408kb | 0.042μs   | ±1.11% |
| UuidStringConversionBench  | benchToBytesConversionOfUuid                                                    |     | 5000 | 5   | 907.264kb | 0.098μs   | ±0.78% |
| UuidStringConversionBench  | benchLazyToBytesConversionOfUuid                                                |     | 5000 | 5   | 907.312kb | 0.174μs   | ±1.01% |
| UuidStringConversionBench  | benchFastStringToBytesConversionOfUuid                                          |     | 5000 | 5   | 907.312kb | 0.056μs   | ±0.82% |
| UuidStringConversionBench  | benchFastBytesToBytesConversionOfUuid                                           |     | 5000 | 5   | 907.312kb | 0.051μs   | ±1.39% |
| UuidStringConversionBench  | benchFastLazyToBytesConversionOfUuid                                            |     | 5000 | 5   | 907.312kb | 0.041μs   | ±0.94% |
| UuidStringConversionBench  | benchToBytesConversionOfPromiscuousUuids                                        |     | 5000 | 5   | 907.408kb | 12.613μs  | ±0.79% |
| UuidStringConversionBench  | benchLazyToBytesConversionOfPromiscuousUuids                                    |     | 5000 | 5   | 907.408kb | 20.807μs  | ±0.89% |
| UuidStringConversionBench  | benchFastStringToBytesConversionOfPromiscuousUuids                              |     | 5000 | 5   | 907.408kb | 8.181μs   | ±1.05% |
| UuidStringConversionBench  | benchFastBytesToBytesConversionOfPromiscuousUuids                               |     | 5000 | 5   | 907.408kb | 7.922μs   | ±0.74% |
| UuidStringConversionBench  | benchFastLazyToBytesConversionOfPromiscuousUuids                                |     | 5000 | 5   | 907.408kb | 6.209μs   | ±0.60% |
| UuidSerializationBench     | benchSerializationOfTinyUuid                                                    |     | 5000 | 5   | 964.872kb | 0.680μs   | ±1.32% |
| UuidSerializationBench     | benchLazySerializationOfTinyUuid                                                |     | 5000 | 5   | 964.920kb | 0.168μs   | ±0.80% |
| UuidSerializationBench     | benchFastStringSerializationOfTinyUuid                                          |     | 5000 | 5   | 964.920kb | 0.204μs   | ±0.71% |
| UuidSerializationBench     | benchFastBytesSerializationOfTinyUuid                                           |     | 5000 | 5   | 964.920kb | 0.201μs   | ±1.12% |
| UuidSerializationBench     | benchFastLazySerializationOfTinyUuid                                            |     | 5000 | 5   | 964.920kb | 0.166μs   | ±0.33% |
| UuidSerializationBench     | benchSerializationOfHugeUuid                                                    |     | 5000 | 5   | 964.872kb | 0.674μs   | ±1.42% |
| UuidSerializationBench     | benchLazySerializationOfHugeUuid                                                |     | 5000 | 5   | 964.920kb | 0.169μs   | ±1.05% |
| UuidSerializationBench     | benchFastStringSerializationOfHugeUuid                                          |     | 5000 | 5   | 964.920kb | 0.203μs   | ±1.12% |
| UuidSerializationBench     | benchFastBytesSerializationOfHugeUuid                                           |     | 5000 | 5   | 964.920kb | 0.205μs   | ±0.52% |
| UuidSerializationBench     | benchFastLazySerializationOfHugeUuid                                            |     | 5000 | 5   | 964.920kb | 0.166μs   | ±0.55% |
| UuidSerializationBench     | benchSerializationOfUuid                                                        |     | 5000 | 5   | 964.872kb | 0.665μs   | ±0.63% |
| UuidSerializationBench     | benchLazySerializationOfUuid                                                    |     | 5000 | 5   | 964.872kb | 0.179μs   | ±1.15% |
| UuidSerializationBench     | benchFastStringSerializationOfUuid                                              |     | 5000 | 5   | 964.920kb | 0.218μs   | ±1.04% |
| UuidSerializationBench     | benchFastBytesSerializationOfUuid                                               |     | 5000 | 5   | 964.920kb | 0.203μs   | ±0.73% |
| UuidSerializationBench     | benchFastLazySerializationOfUuid                                                |     | 5000 | 5   | 964.920kb | 0.166μs   | ±0.70% |
| UuidSerializationBench     | benchSerializationOfPromiscuousUuids                                            |     | 5000 | 5   | 965.400kb | 71.976μs  | ±0.63% |
| UuidSerializationBench     | benchLazySerializationOfPromiscuousUuids                                        |     | 5000 | 5   | 968.344kb | 17.117μs  | ±0.48% |
| UuidSerializationBench     | benchFastStringSerializationOfPromiscuousUuids                                  |     | 5000 | 5   | 968.344kb | 22.788μs  | ±0.87% |
| UuidSerializationBench     | benchFastBytesSerializationOfPromiscuousUuids                                   |     | 5000 | 5   | 965.016kb | 22.886μs  | ±0.96% |
| UuidSerializationBench     | benchFastLazySerializationOfPromiscuousUuids                                    |     | 5000 | 5   | 968.344kb | 17.691μs  | ±1.10% |
| UuidSerializationBench     | benchDeSerializationOfTinyUuid                                                  |     | 5000 | 5   | 973.576kb | 4.486μs   | ±1.04% |
| UuidSerializationBench     | benchLazyDeSerializationOfTinyUuid                                              |     | 5000 | 5   | 965.360kb | 0.295μs   | ±0.93% |
| UuidSerializationBench     | benchFastStringDeSerializationOfTinyUuid                                        |     | 5000 | 5   | 966.368kb | 1.362μs   | ±0.75% |
| UuidSerializationBench     | benchFastBytesDeSerializationOfTinyUuid                                         |     | 5000 | 5   | 966.632kb | 1.239μs   | ±1.12% |
| UuidSerializationBench     | benchFastLazyDeSerializationOfTinyUuid                                          |     | 5000 | 5   | 965.888kb | 0.299μs   | ±1.06% |
| UuidSerializationBench     | benchDeSerializationOfHugeUuid                                                  |     | 5000 | 5   | 965.880kb | 2.175μs   | ±1.01% |
| UuidSerializationBench     | benchLazyDeSerializationOfHugeUuid                                              |     | 5000 | 5   | 965.360kb | 0.293μs   | ±0.44% |
| UuidSerializationBench     | benchFastStringDeSerializationOfHugeUuid                                        |     | 5000 | 5   | 966.368kb | 1.019μs   | ±0.95% |
| UuidSerializationBench     | benchFastBytesDeSerializationOfHugeUuid                                         |     | 5000 | 5   | 966.384kb | 0.939μs   | ±1.04% |
| UuidSerializationBench     | benchFastLazyDeSerializationOfHugeUuid                                          |     | 5000 | 5   | 965.888kb | 0.325μs   | ±0.94% |
| UuidSerializationBench     | benchDeSerializationOfUuid                                                      |     | 5000 | 5   | 965.968kb | 4.321μs   | ±0.94% |
| UuidSerializationBench     | benchLazyDeSerializationOfUuid                                                  |     | 5000 | 5   | 965.312kb | 0.324μs   | ±1.11% |
| UuidSerializationBench     | benchFastStringDeSerializationOfUuid                                            |     | 5000 | 5   | 966.272kb | 1.289μs   | ±0.43% |
| UuidSerializationBench     | benchFastBytesDeSerializationOfUuid                                             |     | 5000 | 5   | 966.632kb | 1.361μs   | ±1.32% |
| UuidSerializationBench     | benchFastLazyDeSerializationOfUuid                                              |     | 5000 | 5   | 965.888kb | 0.304μs   | ±0.75% |
| UuidSerializationBench     | benchDeSerializationOfPromiscuousUuids                                          |     | 5000 | 5   | 998.208kb | 420.544μs | ±0.97% |
| UuidSerializationBench     | benchLazyDeSerializationOfPromiscuousUuids                                      |     | 5000 | 5   | 982.328kb | 29.512μs  | ±1.00% |
| UuidSerializationBench     | benchFastStringDeSerializationOfPromiscuousUuids                                |     | 5000 | 5   | 1.015mb   | 139.373μs | ±0.75% |
| UuidSerializationBench     | benchFastBytesDeSerializationOfPromiscuousUuids                                 |     | 5000 | 5   | 1.023mb   | 144.521μs | ±0.82% |
| UuidSerializationBench     | benchFastLazyDeSerializationOfPromiscuousUuids                                  |     | 5000 | 5   | 984.440kb | 30.205μs  | ±0.62% |
| NonLazyUuidConversionBench | benchStringConversionOfUuid                                                     |     | 5000 | 5   | 907.264kb | 0.454μs   | ±0.73% |
| NonLazyUuidConversionBench | benchFastStringConversionOfUuid                                                 |     | 5000 | 5   | 907.264kb | 0.041μs   | ±1.00% |
| UuidFieldExtractionBench   | benchGetFields                                                                  |     | 5000 | 5   | 907.168kb | 0.038μs   | ±1.01% |
| UuidFieldExtractionBench   | benchLazyGetFields                                                              |     | 5000 | 5   | 907.216kb | 0.066μs   | ±0.89% |
| UuidFieldExtractionBench   | benchFastStringGetFields                                                        |     | 5000 | 5   | 907.264kb | 0.037μs   | ±0.80% |
| UuidFieldExtractionBench   | benchFastBytesGetFields                                                         |     | 5000 | 5   | 907.216kb | 0.037μs   | ±1.10% |
| UuidFieldExtractionBench   | benchFastLazyGetFields                                                          |     | 5000 | 5   | 907.216kb | 0.060μs   | ±0.83% |
| UuidFieldExtractionBench   | benchGetFields10Times                                                           |     | 5000 | 5   | 907.216kb | 0.250μs   | ±1.25% |
| UuidFieldExtractionBench   | benchLazyGetFields10Times                                                       |     | 5000 | 5   | 907.264kb | 0.500μs   | ±0.86% |
| UuidFieldExtractionBench   | benchFastStringGetFields10Times                                                 |     | 5000 | 5   | 907.264kb | 0.235μs   | ±1.00% |
| UuidFieldExtractionBench   | benchFastBytesGetFields10Times                                                  |     | 5000 | 5   | 907.264kb | 0.239μs   | ±0.87% |
| UuidFieldExtractionBench   | benchFastLazyStringGetFields10Times                                             |     | 5000 | 5   | 907.312kb | 0.466μs   | ±0.55% |
| UuidFieldExtractionBench   | benchGetHex                                                                     |     | 5000 | 5   | 907.168kb | 0.886μs   | ±0.81% |
| UuidFieldExtractionBench   | benchLazyGetHex                                                                 |     | 5000 | 5   | 907.168kb | 0.910μs   | ±0.76% |
| UuidFieldExtractionBench   | benchFastStringGetHex                                                           |     | 5000 | 5   | 907.216kb | 0.080μs   | ±0.34% |
| UuidFieldExtractionBench   | benchFastBytesGetHex                                                            |     | 5000 | 5   | 907.216kb | 0.079μs   | ±1.01% |
| UuidFieldExtractionBench   | benchFastLazyStringGetHex                                                       |     | 5000 | 5   | 907.264kb | 0.103μs   | ±1.20% |
| UuidFieldExtractionBench   | benchGetHex10Times                                                              |     | 5000 | 5   | 907.216kb | 8.671μs   | ±0.70% |
| UuidFieldExtractionBench   | benchLazyGetHex10Times                                                          |     | 5000 | 5   | 907.216kb | 9.605μs   | ±0.84% |
| UuidFieldExtractionBench   | benchFastStringGetHex10Times                                                    |     | 5000 | 5   | 907.264kb | 0.314μs   | ±0.62% |
| UuidFieldExtractionBench   | benchFastBytesGetHex10Times                                                     |     | 5000 | 5   | 907.264kb | 0.310μs   | ±0.26% |
| UuidFieldExtractionBench   | benchFastLazyStringGetHex10Times                                                |     | 5000 | 5   | 907.312kb | 0.516μs   | ±0.86% |
| UuidFieldExtractionBench   | benchGetInteger                                                                 |     | 5000 | 5   | 907.168kb | 151.444μs | ±0.57% |
| UuidFieldExtractionBench   | benchLazyGetInteger                                                             |     | 5000 | 5   | 907.216kb | 149.709μs | ±0.79% |
| UuidFieldExtractionBench   | benchFastStringGetInteger                                                       |     | 5000 | 5   | 907.264kb | 0.314μs   | ±1.10% |
| UuidFieldExtractionBench   | benchFastBytesGetInteger                                                        |     | 5000 | 5   | 907.264kb | 0.225μs   | ±0.97% |
| UuidFieldExtractionBench   | benchFastLazyStringGetInteger                                                   |     | 5000 | 5   | 907.264kb | 0.304μs   | ±0.77% |
| UuidFieldExtractionBench   | benchGetInteger10Times                                                          |     | 5000 | 5   | 907.216kb | 1.473ms   | ±0.66% |
| UuidFieldExtractionBench   | benchLazyGetInteger10Times                                                      |     | 5000 | 5   | 907.264kb | 1.494ms   | ±0.41% |
| UuidFieldExtractionBench   | benchFastStringGetInteger10Times                                                |     | 5000 | 5   | 907.312kb | 0.527μs   | ±1.45% |
| UuidFieldExtractionBench   | benchFastBytesGetInteger10Times                                                 |     | 5000 | 5   | 907.264kb | 0.434μs   | ±1.05% |
| UuidFieldExtractionBench   | benchFastLazyStringGetInteger10Times                                            |     | 5000 | 5   | 907.312kb | 0.709μs   | ±0.78% |
| UuidGenerationBench        | benchUuid1GenerationWithoutParameters                                           |     | 5000 | 5   | 907.312kb | 5.004μs   | ±0.98% |
| UuidGenerationBench        | benchFastUuid1GenerationWithoutParameters                                       |     | 5000 | 5   | 907.408kb | 8.366μs   | ±1.13% |
| UuidGenerationBench        | benchFastLazyUuid1GenerationWithoutParameters                                   |     | 5000 | 5   | 907.408kb | 8.034μs   | ±0.80% |
| UuidGenerationBench        | benchUuid1GenerationWithNode                                                    |     | 5000 | 5   | 907.264kb | 4.386μs   | ±0.72% |
| UuidGenerationBench        | benchFastUuid1GenerationWithNode                                                |     | 5000 | 5   | 907.312kb | 7.735μs   | ±0.85% |
| UuidGenerationBench        | benchFastLazyUuid1GenerationWithNode                                            |     | 5000 | 5   | 907.312kb | 7.132μs   | ±0.55% |
| UuidGenerationBench        | benchUuid1GenerationWithNodeAndClockSequence                                    |     | 5000 | 5   | 907.408kb | 4.001μs   | ±1.04% |
| UuidGenerationBench        | benchFastUuid1GenerationWithNodeAndClockSequence                                |     | 5000 | 5   | 907.408kb | 7.221μs   | ±0.81% |
| UuidGenerationBench        | benchFastLazyUuid1GenerationWithNodeAndClockSequence                            |     | 5000 | 5   | 907.408kb | 7.214μs   | ±0.63% |
| UuidGenerationBench        | benchUuid2GenerationWithDomainAndLocalIdentifier                                |     | 5000 | 5   | 907.408kb | 8.624μs   | ±1.08% |
| UuidGenerationBench        | benchFastUuid2GenerationWithDomainAndLocalIdentifier                            |     | 5000 | 5   | 907.408kb | 11.792μs  | ±0.46% |
| UuidGenerationBench        | benchFastLazyUuid2GenerationWithDomainAndLocalIdentifier                        |     | 5000 | 5   | 907.504kb | 11.061μs  | ±0.55% |
| UuidGenerationBench        | benchUuid2GenerationWithDomainAndLocalIdentifierAndNode                         |     | 5000 | 5   | 907.408kb | 7.554μs   | ±0.45% |
| UuidGenerationBench        | benchFastUuid2GenerationWithDomainAndLocalIdentifierAndNode                     |     | 5000 | 5   | 907.504kb | 11.063μs  | ±0.84% |
| UuidGenerationBench        | benchFastLazyUuid2GenerationWithDomainAndLocalIdentifierAndNode                 |     | 5000 | 5   | 907.504kb | 10.481μs  | ±0.88% |
| UuidGenerationBench        | benchUuid2GenerationWithDomainAndLocalIdentifierAndNodeAndClockSequence         |     | 5000 | 5   | 907.504kb | 6.931μs   | ±0.71% |
| UuidGenerationBench        | benchFastUuid2GenerationWithDomainAndLocalIdentifierAndNodeAndClockSequence     |     | 5000 | 5   | 907.600kb | 10.151μs  | ±0.48% |
| UuidGenerationBench        | benchFastLazyUuid2GenerationWithDomainAndLocalIdentifierAndNodeAndClockSequence |     | 5000 | 5   | 907.600kb | 9.862μs   | ±0.98% |
| UuidGenerationBench        | benchUuid3Generation                                                            |     | 5000 | 5   | 907.216kb | 1.413μs   | ±1.02% |
| UuidGenerationBench        | benchLazyUuid3Generation                                                        |     | 5000 | 5   | 907.264kb | 1.512μs   | ±1.31% |
| UuidGenerationBench        | benchFastStringUuid3Generation                                                  |     | 5000 | 5   | 907.264kb | 4.601μs   | ±0.78% |
| UuidGenerationBench        | benchFastBytesUuid3Generation                                                   |     | 5000 | 5   | 907.264kb | 4.610μs   | ±0.71% |
| UuidGenerationBench        | benchFastLazyUuid3Generation                                                    |     | 5000 | 5   | 907.264kb | 4.232μs   | ±1.48% |
| UuidGenerationBench        | benchUuid4Generation                                                            |     | 5000 | 5   | 907.216kb | 1.471μs   | ±0.97% |
| UuidGenerationBench        | benchFastUuid4Generation                                                        |     | 5000 | 5   | 907.264kb | 4.652μs   | ±1.09% |
| UuidGenerationBench        | benchFastLazyUuid4Generation                                                    |     | 5000 | 5   | 907.264kb | 4.386μs   | ±0.92% |
| UuidGenerationBench        | benchUuid5Generation                                                            |     | 5000 | 5   | 907.216kb | 1.515μs   | ±1.32% |
| UuidGenerationBench        | benchLazyUuid5Generation                                                        |     | 5000 | 5   | 907.264kb | 1.571μs   | ±0.55% |
| UuidGenerationBench        | benchFastStringUuid5Generation                                                  |     | 5000 | 5   | 907.264kb | 4.714μs   | ±1.42% |
| UuidGenerationBench        | benchFastBytesUuid5Generation                                                   |     | 5000 | 5   | 907.264kb | 4.727μs   | ±0.49% |
| UuidGenerationBench        | benchFastLazyUuid5Generation                                                    |     | 5000 | 5   | 907.264kb | 4.400μs   | ±1.36% |
| UuidGenerationBench        | benchUuid6GenerationWithoutParameters                                           |     | 5000 | 5   | 907.312kb | 5.837μs   | ±0.50% |
| UuidGenerationBench        | benchFastUuid6GenerationWithoutParameters                                       |     | 5000 | 5   | 907.408kb | 9.298μs   | ±0.41% |
| UuidGenerationBench        | benchFastLazyUuid6GenerationWithoutParameters                                   |     | 5000 | 5   | 907.408kb | 8.772μs   | ±1.38% |
| UuidGenerationBench        | benchUuid6GenerationWithNode                                                    |     | 5000 | 5   | 907.264kb | 4.832μs   | ±0.85% |
| UuidGenerationBench        | benchFastUuid6GenerationWithNode                                                |     | 5000 | 5   | 907.312kb | 8.155μs   | ±1.31% |
| UuidGenerationBench        | benchFastLazyUuid6GenerationWithNode                                            |     | 5000 | 5   | 907.312kb | 7.883μs   | ±1.15% |
| UuidGenerationBench        | benchUuid6GenerationWithNodeAndClockSequence                                    |     | 5000 | 5   | 907.408kb | 4.430μs   | ±0.36% |
| UuidGenerationBench        | benchFastUuid6GenerationWithNodeAndClockSequence                                |     | 5000 | 5   | 907.408kb | 8.162μs   | ±0.51% |
| UuidGenerationBench        | benchFastLazyUuid6GenerationWithNodeAndClockSequence                            |     | 5000 | 5   | 907.408kb | 7.603μs   | ±1.04% |
| UuidGenerationBench        | benchUuid7Generation                                                            |     | 5000 | 5   | 907.216kb | 2.459μs   | ±0.78% |
| UuidGenerationBench        | benchFastUuid7Generation                                                        |     | 5000 | 5   | 907.264kb | 5.812μs   | ±0.85% |
| UuidGenerationBench        | benchFastLazyUuid7Generation                                                    |     | 5000 | 5   | 907.264kb | 5.421μs   | ±1.03% |
| UuidGenerationBench        | benchUuid7GenerationWithDateTime                                                |     | 5000 | 5   | 907.312kb | 2.818μs   | ±0.67% |
| UuidGenerationBench        | benchFastUuid7GenerationWithDateTime                                            |     | 5000 | 5   | 907.312kb | 6.199μs   | ±0.69% |
| UuidGenerationBench        | benchFastLazyUuid7GenerationWithDateTime                                        |     | 5000 | 5   | 907.408kb | 5.648μs   | ±0.94% |
| UuidGenerationBench        | benchUuid8                                                                      |     | 5000 | 5   | 907.168kb | 0.996μs   | ±0.89% |
| UuidGenerationBench        | benchFastUuid8                                                                  |     | 5000 | 5   | 907.168kb | 4.344μs   | ±0.78% |
| UuidGenerationBench        | benchFastLazyUuid8                                                              |     | 5000 | 5   | 907.216kb | 3.874μs   | ±0.65% |
+----------------------------+---------------------------------------------------------------------------------+-----+------+-----+-----------+-----------+--------+
```
Transcript:
- `bench*` without the following `Fast*` and `Lazy*` is `ramsey/uuid` without lazy initialization
- `benchLazy*` is `ramsey/uuid` with lazy initialization
- `benchFast*` without the following `String*` or `Bytes*` is `mougrim/fast-uuid` where we can't use both string-based realization and bytes-based realization (can use only one of them) without lazy initialization
- `benchFastString*` is `mougrim/fast-uuid` string-based realization without lazy initialization
- `benchFastBytes*` is `mougrim/fast-uuid` bytes-based realization without lazy initialization
- `benchLazy*` is `mougrim/fast-uuid` string-based realization with lazy initialization (lazy initialization can't be bytes-based)

As you see, generation isn't optimized, but if needed you can make a MR 🙂
If you use only `fromString` and `toString` conversions, and you don't need DI and custom UUID factory, `ramsey/uuid` will be optimal for you.
In any other cases, it is better to use `mougrim/fast-uuid` with or without lazy initialization.

## Restrictions

- only RFC-4122 and nonstandard UUID variants are supported
- GUID is not supported
- do not use checks like `$uuid instanceof UuidV7`, use `$uuid->getVersion()` or `$uuid->getFields()->getVersion()` to check the version
- string UUID should be in lowercase without 'urn:uuid:' and curly braces ('{' and '}') and should meet `FastStringFields::VALID_PATTERN`
- if you want to use lazy initialization or serialize/deserialize UUID, you have to use the `FastUuidFactoryFactory::createAndSetFactory()` method and only one instance of FastUuidFactory
- changing `$codec` (\Ramsey\Uuid\Codec\CodecInterface) is at your own risk, because in some places converting is used directly without `$codec`

## Copyright and License

The `mougrim/fast-uuid` library is copyright © [Mougrim](https://github.com/mougrim) and licensed for use under the MIT License (MIT). Please see [license.md](https://github.com/mougrim/fast-uuid/blob/main/license.md) for more information.
