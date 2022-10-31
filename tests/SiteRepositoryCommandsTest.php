<?php

namespace PhpSiteRepositoryTool;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class SiteRepositoryCommandsTest extends TestCase implements CommandTesterInterface
{
    use CommandTesterTrait;

    /** @var string[] */
    protected $commandClasses;

    /**
     * Prepare to test our commandfile
     */
    public function setUp(): void
    {
        // Store the command classes we are going to test
        $this->commandClasses = [ \PhpSiteRepositoryTool\Cli\SiteRepositoryCommands::class ];
        $this->setupCommandTester('TestFixtureApp', '1.0.1');
    }

    /**
     * Data provider for testCommandsExistence.
     */
    public function commandsExistenceParameters()
    {
        return [
            [
                'Apply upstream command',
                self::STATUS_OK,
                'list',
            ],
            [
                'Apply upstream command',
                self::STATUS_OK,
                'list',
            ],
        ];
    }

    /**
     * Test that the given commands actually exist.
     *
     * @dataProvider commandsExistenceParameters
     */
    public function testCommandsExistence($expectedOutput, $expectedStatus, $variable_args)
    {
        // Set this to the path to a fixture configuration file if you'd like to use one.
        $configurationFile = false;

        // Create our argv array and run the command
        $argv = $this->argv(func_get_args());
        list($actualOutput, $statusCode) = $this->execute($argv, $this->commandClasses, $configurationFile);

        // Confirm that our output and status code match expectations
        $this->assertStringContainsString($expectedOutput, $actualOutput);
        $this->assertEquals($expectedStatus, $statusCode);
    }
}
