<?php

namespace PhpSiteRepositoryTool;

use PhpSiteRepositoryTool\Utils\Git;
use PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Class GitTest.
 */
class GitTest extends TestCase
{
    use SiteRepositoryToolTesterTrait;

    /**
     * @var \PhpSiteRepositoryTool\Utils\Git
     */
    protected static $git;

    /**
     * @var string
     */
    protected static $upstreamUrl;

    /**
     * Prepare to test our class.
     */
    public static function set_up_before_class()
    {
        parent::set_up_before_class();

        $workdir = sys_get_temp_dir() . '/php-site-repository-tool-test-' . uniqid();
        mkdir($workdir);
        self::$git = new Git('', '', $workdir, true);
        self::$upstreamUrl = 'https://'. getenv('GITHUB_TOKEN') . '@github.com/pantheon-fixtures/php-srt-upstream-fixture.git';
    }

    /**
     * Test clone function.
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     * @throws \PhpSiteRepositoryTool\Exceptions\NotEmptyFolderException
     */
    public function testClone()
    {
        self::$git->cloneRepository(self::$upstreamUrl, 'main');
        $this->assertFileExists(self::$git->getWorkdir() . '/.git');
    }

    /**
     * Test adding a remote.
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function testRemoteAdd()
    {
        self::$git->remoteAdd('upstream', self::$upstreamUrl);
        $output = $this->callMethod(self::$git, 'execute', [['remote', 'show', 'upstream']]);
        $this->assertStringContainsString('Fetch URL: ' . self::$upstreamUrl, $output);
    }

    /**
     * Test fetching a remote.
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function testFetch()
    {
        $output = self::$git->fetch('upstream');
        $this->assertEquals('', $output);
    }

    /**
     * Test merging a branch.
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException
     */
    public function testCleanMerge()
    {
        $output = self::$git->merge('clean-merge', 'upstream');
        $this->assertEquals('', $output);
    }

    /**
     * Test merging a branch.
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function testMergeWithConflicts()
    {
        try {
            self::$git->merge('branch-to-merge', 'upstream');
        } catch (GitMergeConflictException $e) {
            $files = self::$git->listUnmergedFiles();
            $this->assertCount(1, $files);
            $this->assertContains('README.md', $files);
        }
    }

    /**
     * Test removing a file.
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function testRemove()
    {
        $output = self::$git->remove(['README.md']);
        $this->assertStringContainsString("rm 'README.md'", $output);
    }

    /**
     * Test getting remote message.
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function testGetRemoteMessage()
    {
        $message = self::$git->getRemoteMessage('clean-merge');
        $this->assertEquals('Add new commit here.', $message);
    }
}
