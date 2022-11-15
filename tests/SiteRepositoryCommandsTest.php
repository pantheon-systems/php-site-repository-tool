<?php

namespace PhpSiteRepositoryTool;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Class SiteRepositoryCommandsTest.
 */
class SiteRepositoryCommandsTest extends TestCase implements CommandTesterInterface
{
    use CommandTesterTrait;

    /**
     * @var string[]
     */
    protected $commandClasses;

    /**
     * Prepare to test our commandfile
     */
    // phpcs:ignore
    public function set_up()
    {
        parent::set_up();

        // Store the command classes we are going to test
        $this->commandClasses = [\PhpSiteRepositoryTool\Cli\SiteRepositoryCommands::class];
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
                'Merge Environment command',
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
        // Create our argv array and run the command
        $argv = $this->argv(func_get_args());
        list($actualOutput, $statusCode) = $this->execute($argv, $this->commandClasses);

        // Confirm that our output and status code match expectations
        $this->assertStringContainsString($expectedOutput, $actualOutput);
        $this->assertEquals($expectedStatus, $statusCode);
    }

    /**
     * Test apply upstream command.
     */
    public function testApplyUpstream()
    {
        $result = $this->executeApplyUpstreamCommand('main');
        $this->assertEquals([
            'clone' => true,
            'pull' => true,
            'push' => false,
            'logs' => [
                'Repository has been cloned',
                'Upstream remote has been added',
                'Updates have been fetched',
                'Updates have been merged',
                'Updates have been committed',
            ],
            'conflicts' => '',
            'errormessage' => '',
        ], $result);
    }

    /**
     * Test apply upstream command with --update-behavior="procedural" option and an unmerged off-switch update.
     */
    public function testApplyUpstreamBehaviorProceduralOffSwitch()
    {
        $result = $this->executeApplyUpstreamCommand('unmerged-changes-in-upstream', 'procedural');
        $this->assertEquals([
            'clone' => true,
            'pull' => true,
            'push' => false,
            'logs' => [
                'Repository has been cloned',
                'Upstream remote has been added',
                'Updates have been fetched',
                'An unmerged off-switch update found',
            ],
            'conflicts' => '',
            'errormessage' => '',
        ], $result);
    }

    /**
     * Test apply upstream command with --update-behavior="procedural" option.
     */
    public function testApplyUpstreamBehaviorProceduralNoOffSwitch()
    {
        $result = $this->executeApplyUpstreamCommand('main', 'procedural');
        $this->assertEquals([
            'clone' => true,
            'pull' => true,
            'push' => false,
            'logs' => [
                'Repository has been cloned',
                'Upstream remote has been added',
                'Updates have been fetched',
                'Updates have been merged',
                'Updates have been committed',
            ],
            'conflicts' => '',
            'errormessage' => '',
        ], $result);
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
        list($output, $statusCode) = $this->execute($argv, $this->commandClasses);
        $result = json_decode($output, true);
        $this->assertEquals(self::STATUS_OK, $statusCode);
        $this->assertEquals([
            'clone' => true,
            'pull' => true,
            'push' => false,
            'logs' => [
                'Repository has been cloned',
                'Updates have been merged',
                'Updates have been committed',
            ],
            'conflicts' => '',
            'errormessage' => '',
        ], $result);
    }

    /**
     * Executes the command and return the result.
     *
     * @param string $upstreamRepoBranch
     * @param string $updateBehavior
     *
     * @return mixed
     */
    private function executeApplyUpstreamCommand($upstreamRepoBranch, $updateBehavior = 'heirloom')
    {
        $workdir = sys_get_temp_dir() . '/php-site-repository-tool-test-' . uniqid();
        mkdir($workdir);

        $siteRepoUrl = 'https://' . $this->getGithubToken() . '@github.com/pantheon-fixtures/php-srt-site-fixture.git';
        $siteRepoBranch = 'master';
        $upstreamRepoUrl = 'https://' . $this->getGithubToken() . '@github.com/pantheon-fixtures/php-srt-upstream-fixture.git';

        $argv = $this->argv([
            'apply_upstream',
            '--site-repo-url=' . $siteRepoUrl,
            '--site-repo-branch=' . $siteRepoBranch,
            '--upstream-repo-url=' . $upstreamRepoUrl,
            '--upstream-repo-branch=' . $upstreamRepoBranch,
            '--work-dir=' . $workdir,
            '--update-behavior=' . $updateBehavior,
            // Do not push to avoid altering the fixture repository.
            '--no-push',
            '--verbose',
        ], 0);

        list($output, $statusCode) = $this->execute($argv, $this->commandClasses);
        $result = json_decode($output, true);
        $this->assertEquals(self::STATUS_OK, $statusCode);

        return $result;
    }
}
