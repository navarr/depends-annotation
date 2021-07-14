<?php

/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

declare(strict_types=1);

namespace Navarr\Depends\Controller\Common\WhyBlockUtility;

use JetBrains\PhpStorm\Immutable;

#[Immutable(Immutable::CONSTRUCTOR_WRITE_SCOPE)]
class OptionValues
{
    /** @var bool */
    private $failOnError;

    /** @var string */
    private $format;

    /** @var bool */
    private $includeLegacy;

    /** @var string */
    private $package;

    /** @var string */
    private $version;

    public function __construct(
        string $package,
        string $version,
        string $format,
        bool $includeLegacy = true,
        bool $failOnError = false
    ) {
        $this->package = $package;
        $this->version = $version;
        $this->format = $format;
        $this->includeLegacy = $includeLegacy;
        $this->failOnError = $failOnError;
    }

    public function getPackageToSearchFor(): string
    {
        return $this->package;
    }

    public function getVersionToCompareTo(): string
    {
        return $this->version;
    }

    public function getOutputFormat(): string
    {
        return $this->format;
    }

    public function shouldIncludeAnnotationsInSearch(): bool
    {
        return $this->includeLegacy;
    }

    public function shouldFailOnParseError(): bool
    {
        return $this->failOnError;
    }
}
