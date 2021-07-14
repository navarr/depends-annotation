<?php

/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

declare(strict_types=1);

namespace Navarr\Depends\Controller\Di;

use DI\ContainerBuilder;
use Navarr\Depends\Proxy\StdOutWriter;
use Navarr\Depends\Proxy\WriterInterface;

use function DI\autowire;

class AddDefaultDefinitions
{
    public static function execute(ContainerBuilder $containerBuilder = null): ContainerBuilder
    {
        $containerBuilder = $containerBuilder ?? new ContainerBuilder();
        $containerBuilder->addDefinitions(
            [
                WriterInterface::class => autowire(StdOutWriter::class),
            ]
        );
        return $containerBuilder;
    }
}
