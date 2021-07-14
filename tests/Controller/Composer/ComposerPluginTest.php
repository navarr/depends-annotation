<?php
/**
 * @copyright 2021 Navarr Barnier. All Rights Reserved.
 */

namespace Navarr\Depends\Test\Controller\Composer;

use Composer\Plugin\Capability\CommandProvider;
use Navarr\Depends\Controller\Composer\WhyBlockComposerCommand;
use Navarr\Depends\Controller\Composer\ComposerPlugin;
use PHPUnit\Framework\TestCase;

class ComposerPluginTest extends TestCase
{

    public function testCommandProviderIsPartOfPlugin()
    {
        $plugin = new ComposerPlugin();
        $capabilities = $plugin->getCapabilities();
        $this->assertArrayHasKey(CommandProvider::class, $capabilities);
        $this->assertEquals(ComposerPlugin::class, $capabilities[CommandProvider::class]);
    }

    public function testContainsOnlyComposerCommands()
    {
        $plugin = new ComposerPlugin();
        $commands = $plugin->getCommands();

        $this->assertIsArray($commands);
        $this->assertCount(1, $commands);
        $command = end($commands);
        $this->assertInstanceOf(WhyBlockComposerCommand::class, $command);
    }
}
