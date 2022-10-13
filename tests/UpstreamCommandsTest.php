<?php

namespace PhpSiteRepositoryTool;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

class UpstreamCommandsTest extends TestCase implements CommandTesterInterface
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
        $this->commandClasses = [ \PhpSiteRepositoryTool\Cli\UpstreamCommands::class ];
        $this->setupCommandTester('TestFixtureApp', '1.0.1');
    }

    /**
     * Data provider for testExampleCommands.
     *
     * Return an array of arrays, each of which contains the data for one test.
     * The parameters in each array should be:
     *
     *   - Expected output (actual output must CONTAIN this string)
     *   - Expected function status code
     *   - argv
     *
     * All of the remaining parameters after the first two are interpreted
     * to be the argv value to pass to the command. The application name
     * is automatically unshifted into argv[0] first.
     */
    public function exampleTestCommandParameters()
    {
        return [

            [
                'Done.',
                self::STATUS_OK,
                'apply_upstream'
            ],

            [
                'Apply upstream command scaffolding',
                self::STATUS_OK,
                'list',
            ]
        ];
    }

    /**
     * Test our example commandfile class. Each time this function is called,
     * it will be passed the expected output and expected status code; the
     * remainder of the arguments passed will be used as $argv.
     *
     * @dataProvider exampleTestCommandParameters
     */
    public function testExampleCommands($expectedOutput, $expectedStatus, $variable_args)
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
