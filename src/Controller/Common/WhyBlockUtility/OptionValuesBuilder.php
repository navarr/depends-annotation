<?php

/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

declare(strict_types=1);

namespace Navarr\Depends\Controller\Common\WhyBlockUtility;

use InvalidArgumentException;
use Navarr\Depends\Controller\Common\WhyBlockUtility;
use Symfony\Component\Console\Input\InputInterface;

class OptionValuesBuilder
{
    public function buildFromInput(InputInterface $input): OptionValues
    {
        $packageToSearchFor = $input->getArgument(WhyBlockUtility::ARGUMENT_PACKAGE);
        $versionToCompareTo = $input->getArgument(WhyBlockUtility::ARGUMENT_VERSION);
        $outputFormat = $input->getOption(WhyBlockUtility::OPTION_OUTPUT_FORMAT);

        if (!is_string($packageToSearchFor)) {
            throw new InvalidArgumentException('Only one package is allowed');
        }
        if (!is_string($versionToCompareTo)) {
            throw new InvalidArgumentException('Only one version is allowed');
        }
        if (!is_string($outputFormat)) {
            throw new InvalidArgumentException('Only one output format is allowed');
        }

        $outputFormat = strtolower($outputFormat);
        if (!in_array($outputFormat, WhyBlockUtility::ACCEPTABLE_FORMATS)) {
            $outputFormat = 'text';
        }

        $failOnError = (bool)$input->getOption(WhyBlockUtility::OPTION_FAIL_ON_ERROR);
        $includeAnnotations = (bool)$input->getOption(WhyBlockUtility::OPTION_INCLUDE_ANNOTATIONS);

        return new OptionValues(
            $packageToSearchFor,
            $versionToCompareTo,
            $outputFormat,
            $includeAnnotations,
            $failOnError
        );
    }
}
