<?php

/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

declare(strict_types=1);

namespace Navarr\Depends\Controller\Cli;

use InvalidArgumentException;
use Navarr\Attribute\Dependency;
use Navarr\Depends\Command\WhyBlockCommand;
use Navarr\Depends\Controller\Common\WhyBlockUtility;
use Navarr\Depends\Controller\Di\AddDefaultDefinitions;
use Navarr\Depends\ScopeDeterminer\DirectoryScopeDeterminer;
use Navarr\Depends\ScopeDeterminer\PhpFileFinder;
use Navarr\Depends\ScopeDeterminer\ScopeDeterminerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WhyBlockCliCommand extends Command
{
    private const ARGUMENT_DIR = 'directory';

    // phpcs:disable Generic.Files.LineLength.TooLong -- Attribute support pre PHP 8
    #[Dependency('symfony/console', '^5', 'Command\'s setName, addArgument and addOption methods as well as InputArgument\'s constants of REQUIRED and VALUE_NONE')]
    #[Dependency('php-di/php-di', '^6', 'DI\ContainerBuilder::addDefinitions and the existence of the DI\autowire function')]
    // phpcs:enable Generic.Files.LineLength.TooLong
    protected function configure(): void
    {
        WhyBlockUtility::addCommongArguments($this)
            ->addArgument('directory', InputArgument::REQUIRED, 'Directory to search in');
    }

    #[Dependency('symfony/console', '^5', 'InputInterface::getOption and OutputInterface::writeln')]
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $optionValuesBuilder = new WhyBlockUtility\OptionValuesBuilder();
        $optionValues = $optionValuesBuilder->buildFromInput($input);
        $directory = $input->getArgument('directory');

        if (!is_string($directory)) {
            throw new InvalidArgumentException('Only one directory is allowed');
        }

        $containerBuilder = AddDefaultDefinitions::execute();
        WhyBlockUtility::addBaseDiDefinitions($containerBuilder, $input, $output, $optionValues);
        $containerBuilder->addDefinitions(
            [
                ScopeDeterminerInterface::class => static function (ContainerInterface $container) use ($directory) {
                    return new DirectoryScopeDeterminer(
                        $container->get(PhpFileFinder::class),
                        $directory
                    );
                },
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
