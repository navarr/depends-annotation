<?php

/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

declare(strict_types=1);

namespace Navarr\Depends\Controller\Composer;

use Composer\Command\BaseCommand;
use Composer\Composer;
use DI\ContainerBuilder;
use InvalidArgumentException;
use Navarr\Attribute\Dependency;
use Navarr\Depends\Command\WhyBlockCommand;
use Navarr\Depends\Command\WhyBlockCommand\CsvOutputHandler;
use Navarr\Depends\Command\WhyBlockCommand\JsonOutputHandler;
use Navarr\Depends\Command\WhyBlockCommand\StandardOutputHandler;
use Navarr\Depends\Command\WhyBlockCommand\OutputHandlerInterface;
use Navarr\Depends\Command\WhyBlockCommand\XmlOutputHandler;
use Navarr\Depends\IssueHandler\FailOnIssueHandler;
use Navarr\Depends\IssueHandler\IssueHandlerInterface;
use Navarr\Depends\IssueHandler\NotifyOnIssueHandler;
use Navarr\Depends\Parser\AstParser;
use Navarr\Depends\Parser\LegacyParser;
use Navarr\Depends\Parser\ParserInterface;
use Navarr\Depends\Parser\ParserPool;
use Navarr\Depends\ScopeDeterminer\ComposerScopeDeterminer;
use Navarr\Depends\ScopeDeterminer\ScopeDeterminerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function DI\autowire;

#[Dependency('composer/composer', '^1|^2', 'Extends BaseCommand')]
class ComposerCommand extends BaseCommand
{
    private const ALL_DEPS = 'include-all-dependencies';
    private const ROOT_DEPS = 'include-root-dependencies';
    private const LEGACY_ANNOTATION = 'include-legacy-annotations';
    private const FAIL_ON_ERROR = 'fail-on-error';
    private const OUTPUT_FORMAT = 'format';

    private const FORMAT_TEXT = 'text';
    private const FORMAT_CSV = 'csv';
    private const FORMAT_JSON = 'json';
    private const FORMAT_XML = 'xml';

    private const ACCEPTABLE_FORMATS = [
        self::FORMAT_CSV,
        self::FORMAT_TEXT,
        self::FORMAT_JSON,
        self::FORMAT_XML,
    ];

    private const FORMAT_MAPPER = [
        self::FORMAT_CSV => CsvOutputHandler::class,
        self::FORMAT_TEXT => StandardOutputHandler::class,
        self::FORMAT_JSON => JsonOutputHandler::class,
        self::FORMAT_XML => XmlOutputHandler::class,
    ];

    // phpcs:disable Generic.Files.LineLength.TooLong -- Attribute support pre PHP 8
    #[Dependency('symfony/console', '^5', 'Command\'s setName, addArgument and addOption methods as well as InputArgument\'s constants of REQUIRED and VALUE_NONE')]
    #[Dependency('php-di/php-di', '^6', 'DI\ContainerBuilder::addDefinitions and the existence of the DI\autowire function')]
    // phpcs:enable Generic.Files.LineLength.TooLong
    protected function configure(): void
    {
        $this->setName('why-block')
            ->addArgument('package', InputArgument::REQUIRED, 'Package to inspect')
            ->addArgument('version', InputArgument::REQUIRED, 'Version you want to update it to')
            ->addOption(
                self::OUTPUT_FORMAT,
                ['f'],
                InputOption::VALUE_OPTIONAL,
                'Format to output results in.  Accepted values: text, csv, json, xml',
                'text'
            )
            ->addOption(
                self::FAIL_ON_ERROR,
                ['e'],
                InputOption::VALUE_NONE,
                'Immediately fail on parsing errors'
            )
            ->addOption(
                self::LEGACY_ANNOTATION,
                ['l'],
                InputOption::VALUE_NONE,
                'Include old @dependency/@composerDependency annotations in search'
            )
            ->addOption(
                self::ROOT_DEPS,
                ['r'],
                InputOption::VALUE_NONE,
                'Search root dependencies for the @dependency annotation'
            )
            ->addOption(
                self::ALL_DEPS,
                ['a'],
                InputOption::VALUE_NONE,
                'Search all dependencies for the @dependency annotation'
            );
    }

    #[Dependency('symfony/console', '^5', 'InputInterface::getOption and OutputInterface::writeln')]
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $packageToSearchFor = $input->getArgument('package');
        $versionToCompareTo = $input->getArgument('version');
        $outputFormat = $input->getOption(self::OUTPUT_FORMAT);

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
        if (!in_array($outputFormat, static::ACCEPTABLE_FORMATS)) {
            $outputFormat = 'text';
        }

        if ($input->getOption(static::ALL_DEPS)) {
            $composerScope = ComposerScopeDeterminer::SCOPE_ALL_DEPENDENCIES;
        } elseif ($input->getOption(static::ROOT_DEPS)) {
            $composerScope = ComposerScopeDeterminer::SCOPE_ROOT_DEPENDENCIES;
        } else {
            $composerScope = ComposerScopeDeterminer::SCOPE_PROJECT_ONLY;
        }

        $containerBuilder = new ContainerBuilder();
        $containerBuilder->addDefinitions(
            [
                InputInterface::class => $input,
                OutputInterface::class => $output,
                IssueHandlerInterface::class => $input->getOption(static::FAIL_ON_ERROR)
                    ? FailOnIssueHandler::class
                    : NotifyOnIssueHandler::class,
                Composer::class => $this->getComposer(true),
                ParserInterface::class => static function (ContainerInterface $container) use ($input) {
                    $parsers = [$container->get(AstParser::class)];
                    if ($input->getOption(static::LEGACY_ANNOTATION)) {
                        $parsers[] = $container->get(LegacyParser::class);
                    }
                    return new ParserPool($parsers);
                },
                ComposerScopeDeterminer::class => autowire(ComposerScopeDeterminer::class)
                    ->property('scope', $composerScope),
                ScopeDeterminerInterface::class => autowire(ComposerScopeDeterminer::class),
                OutputHandlerInterface::class => autowire(static::FORMAT_MAPPER[$outputFormat]),
            ]
        );
        $container = $containerBuilder->build();

        /** @var WhyBlockCommand $command */
        $command = $container->get(WhyBlockCommand::class);
        return $command->execute($packageToSearchFor, $versionToCompareTo);
    }
}
