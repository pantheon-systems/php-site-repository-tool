<?php

namespace PhpSiteRepositoryTool;

use Symfony\Component\Console\Output\BufferedOutput;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

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

    /**
     * Test apply upstream command.
     */
    public function testApplyUpstream()
    {
        $workdir = sys_get_temp_dir() . '/php-site-repository-tool-test-' . uniqid();
        mkdir($workdir);

        $siteRepoUrl = 'https://' . $this->getGithubToken() . '@github.com/pantheon-fixtures/php-srt-site-fixture.git';
        $siteRepoBranch = 'master';
        $upstreamRepoUrl = 'https://'. $this->getGithubToken() . '@github.com/pantheon-fixtures/php-srt-upstream-fixture.git';
        $upstreamRepoBranch = 'main';
 
        $argv = $this->argv([
            'apply_upstream',
            '--site-repo-url=' . $siteRepoUrl,
            '--site-repo-branch=' . $siteRepoBranch,
            '--upstream-repo-url=' . $upstreamRepoUrl,
            '--upstream-repo-branch=' . $upstreamRepoBranch,
            '--work-dir=' . $workdir,
            '--update-behavior=heirloom',
            // Do not push to avoid altering the fixture repository.
            '--no-push',
            '--verbose',
        ], 0);
        list($actualOutput, $statusCode) = $this->execute($argv, $this->commandClasses, false);
        $jsonOutput = json_decode($actualOutput, true);
        $this->assertEquals(self::STATUS_OK, $statusCode);
        $this->assertEquals([
            'clone' => true,
            'pull' => true,
            'push' => false,
            'conflicts' => '',
            'errormessage' => '',
        ], $jsonOutput);
    }

    /**
     * Test merge_environment command.
     */
    public function testMergeEnvironment()
    {
        $workdir = sys_get_temp_dir() . '/php-site-repository-tool-test-' . uniqid();
        mkdir($workdir);

        $siteRepoUrl = 'https://' . $this->getGithubToken() . '@github.com/pantheon-fixtures/php-srt-site-fixture.git';
        $siteRepoBranch = 'master';
        $upstreamRepoUrl = 'https://'. $this->getGithubToken() . '@github.com/pantheon-fixtures/php-srt-upstream-fixture.git';
        $upstreamRepoBranch = 'main';
 
        $argv = $this->argv([
            'merge_environment',
            '--site-repo-url=' . $siteRepoUrl,
            '--site-repo-branch=' . $siteRepoBranch,
            '--from-branch=' . 'featureX',
            '--to-branch=' . $siteRepoBranch,
            '--work-dir=' . $workdir,
            // Do not push to avoid altering the fixture repository.
            '--no-push',
            '--verbose',
        ], 0);
        list($actualOutput, $statusCode) = $this->execute($argv, $this->commandClasses, false);
        $jsonOutput = json_decode($actualOutput, true);
        $this->assertEquals(self::STATUS_OK, $statusCode);
        $this->assertEquals([
            'clone' => true,
            'pull' => true,
            'push' => false,
            'conflicts' => '',
            'errormessage' => '',
        ], $jsonOutput);
    }
}
