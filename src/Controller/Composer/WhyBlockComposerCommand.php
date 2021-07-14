<?php

/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

declare(strict_types=1);

namespace Navarr\Depends\Controller\Composer;

use Composer\Command\BaseCommand;
use Composer\Composer;
use Navarr\Attribute\Dependency;
use Navarr\Depends\Command\WhyBlockCommand;
use Navarr\Depends\Controller\Common\WhyBlockUtility;
use Navarr\Depends\Controller\Di\AddDefaultDefinitions;
use Navarr\Depends\ScopeDeterminer\ComposerScopeDeterminer;
use Navarr\Depends\ScopeDeterminer\ScopeDeterminerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function DI\autowire;

#[Dependency('composer/composer', '^1|^2', 'Extends BaseCommand')]
class WhyBlockComposerCommand extends BaseCommand
{
    private const OPTION_ALL_DEPS = 'include-all-dependencies';
    private const OPTION_ROOT_DEPS = 'include-root-dependencies';

    // phpcs:disable Generic.Files.LineLength.TooLong -- Attribute support pre PHP 8
    #[Dependency('symfony/console', '^5', 'Command\'s setName, addArgument and addOption methods as well as InputArgument\'s constants of REQUIRED and VALUE_NONE')]
    #[Dependency('php-di/php-di', '^6', 'DI\ContainerBuilder::addDefinitions and the existence of the DI\autowire function')]
    // phpcs:enable Generic.Files.LineLength.TooLong
    protected function configure(): void
    {
        WhyBlockUtility::addCommongArguments($this)
            ->addOption(
                self::OPTION_ROOT_DEPS,
                ['r'],
                InputOption::VALUE_NONE,
                'Search root dependencies for the @dependency annotation'
            )
            ->addOption(
                self::OPTION_ALL_DEPS,
                ['a'],
                InputOption::VALUE_NONE,
                'Search all dependencies for the @dependency annotation'
            );
    }

    // phpcs:disable Generic.Files.LineLength.TooLong -- Attribute support pre PHP 8
    #[Dependency('symfony/console', '^5', 'InputInterface::getOption and OutputInterface::writeln')]
    #[Dependency('php-di/php-di', '^6', 'DI\ContainerBuilder::addDefinitions and the existence of the DI\autowire function')]
    // phpcs:enable Generic.Files.LineLength.TooLong
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $optionValuesBuilder = new WhyBlockUtility\OptionValuesBuilder();
        $optionValues = $optionValuesBuilder->buildFromInput($input);

        if ($input->getOption(static::OPTION_ALL_DEPS)) {
            $composerScope = ComposerScopeDeterminer::SCOPE_ALL_DEPENDENCIES;
        } elseif ($input->getOption(static::OPTION_ROOT_DEPS)) {
            $composerScope = ComposerScopeDeterminer::SCOPE_ROOT_DEPENDENCIES;
        } else {
            $composerScope = ComposerScopeDeterminer::SCOPE_PROJECT_ONLY;
        }

        $containerBuilder = AddDefaultDefinitions::execute();
        WhyBlockUtility::addBaseDiDefinitions($containerBuilder, $input, $output, $optionValues);
        $containerBuilder->addDefinitions(
            [
                Composer::class => $this->getComposer(true),
                ComposerScopeDeterminer::class => autowire(ComposerScopeDeterminer::class)
                    ->property('scope', $composerScope),
                ScopeDeterminerInterface::class => autowire(ComposerScopeDeterminer::class),
            ]
        );
        $container = $containerBuilder->build();

        /** @var WhyBlockCommand $command */
        $command = $container->get(WhyBlockCommand::class);
        return $command->execute(
            $optionValues->getPackageToSearchFor(),
            $optionValues->getVersionToCompareTo()
        );
    }
}
