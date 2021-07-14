<?php

/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

declare(strict_types=1);

namespace Navarr\Depends\Controller;

use Navarr\Attribute\Dependency;
use Navarr\Depends\Controller\Cli\WhyBlockCliCommand;
use Symfony\Component\Console\Application;

#[Dependency('symfony/console', '^5', 'Creates a Symfony Application')]
class CliApplication
{
    private const VERSION = '2.1.0';

    public static function execute(): int
    {
        $application = new Application('DepAnno', static::VERSION);
        $application->add(new WhyBlockCliCommand());
        return $application->run();
    }
}
