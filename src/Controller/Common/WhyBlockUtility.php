<?php

/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

declare(strict_types=1);

namespace Navarr\Depends\Controller\Common;

use DI\ContainerBuilder;
use Navarr\Attribute\Dependency;
use Navarr\Depends\Command\WhyBlockCommand\CsvOutputHandler;
use Navarr\Depends\Command\WhyBlockCommand\JsonOutputHandler;
use Navarr\Depends\Command\WhyBlockCommand\OutputHandlerInterface;
use Navarr\Depends\Command\WhyBlockCommand\StandardOutputHandler;
use Navarr\Depends\Command\WhyBlockCommand\XmlOutputHandler;
use Navarr\Depends\Controller\Common\WhyBlockUtility\OptionValues;
use Navarr\Depends\IssueHandler\FailOnIssueHandler;
use Navarr\Depends\IssueHandler\IssueHandlerInterface;
use Navarr\Depends\IssueHandler\NotifyOnIssueHandler;
use Navarr\Depends\Parser\AstParser;
use Navarr\Depends\Parser\LegacyParser;
use Navarr\Depends\Parser\ParserInterface;
use Navarr\Depends\Parser\ParserPool;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function DI\autowire;

class WhyBlockUtility
{
    public const ARGUMENT_PACKAGE = 'package';
    public const ARGUMENT_VERSION = 'version';

    public const OPTION_OUTPUT_FORMAT = 'format';
    public const OPTION_FAIL_ON_ERROR = 'fail-on-error';
    public const OPTION_INCLUDE_ANNOTATIONS = 'include-legacy-annotations';

    public const FORMAT_TEXT = 'text';
    public const FORMAT_CSV = 'csv';
    public const FORMAT_JSON = 'json';
    public const FORMAT_XML = 'xml';

    public const ACCEPTABLE_FORMATS = [
        self::FORMAT_CSV,
        self::FORMAT_TEXT,
        self::FORMAT_JSON,
        self::FORMAT_XML,
    ];

    public const FORMAT_MAPPER = [
        self::FORMAT_CSV => CsvOutputHandler::class,
        self::FORMAT_TEXT => StandardOutputHandler::class,
        self::FORMAT_JSON => JsonOutputHandler::class,
        self::FORMAT_XML => XmlOutputHandler::class,
    ];

    // phpcs:ignore Generic.Files.LineLength.TooLong -- Attribute support pre PHP 8
    #[Dependency('symfony/console', '^5', 'Command\'s setName, addArgument and addOption methods as well as InputArgument\'s constants of REQUIRED and VALUE_NONE')]
    public static function addCommongArguments(
        Command $command
    ): Command {
        return $command->setName('why-block')
            ->addArgument(
                static::ARGUMENT_PACKAGE,
                InputArgument::REQUIRED,
                'Package to search dependency attributes for'
            )
            ->addArgument(
                static::ARGUMENT_VERSION,
                InputArgument::REQUIRED,
                'Version you want to update the package to'
            )
            ->addOption(
                static::OPTION_OUTPUT_FORMAT,
                ['f'],
                InputOption::VALUE_OPTIONAL,
                'Format to put results in.  Accepted values: text, csv, json, xml'
            )
            ->addOption(
                static::OPTION_FAIL_ON_ERROR,
                ['e'],
                InputOption::VALUE_NONE,
                'Immediately fail on parsing errors'
            )
            ->addOption(
                static::OPTION_INCLUDE_ANNOTATIONS,
                ['l'],
                InputOption::VALUE_NONE,
                'Include old @dependency/@composerDependency annotations in search'
            );
    }

    public static function addBaseDiDefinitions(
        ContainerBuilder $containerBuilder,
        InputInterface $input,
        OutputInterface $output,
        OptionValues $optionValues
    ): ContainerBuilder {
        $formatMapper = WhyBlockUtility::FORMAT_MAPPER[$optionValues->getOutputFormat()];
        $containerBuilder->addDefinitions(
            [
                InputInterface::class => $input,
                OutputInterface::class => $output,
                IssueHandlerInterface::class => $optionValues->shouldFailOnParseError()
                    ? FailOnIssueHandler::class
                    : NotifyOnIssueHandler::class,
                ParserInterface::class => static function (ContainerInterface $container) use ($optionValues) {
                    $parsers = [$container->get(AstParser::class)];
                    if ($optionValues->shouldIncludeAnnotationsInSearch()) {
                        $parsers[] = $container->get(LegacyParser::class);
                    }
                    return new ParserPool($parsers);
                },
                OutputHandlerInterface::class => autowire($formatMapper),
            ]
        );
        return $containerBuilder;
    }
}
