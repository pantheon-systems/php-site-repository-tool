<?php

namespace PhpSiteRepositoryTool;

use PhpSiteRepositoryTool\Utils\Git;
use PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class GitTest extends TestCase
{

    use SiteRepositoryToolTesterTrait;

    /** @var \PhpSiteRepositoryTool\Utils\Git[] */
    protected static $git;

    /** @var string */
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
     */
    public function testClone()
    {
        self::$git->clone(self::$upstreamUrl, 'main');
        $this->assertFileExists(self::$git->getWorkdir() . '/.git');
    }

    /**
     * Test adding a remote.
     */
    public function testRemoteAdd()
    {
        self::$git->remoteAdd('upstream', self::$upstreamUrl);
        $output = $this->callMethod(self::$git, 'execute', [['remote', 'show', 'upstream']]);
        $this->assertStringContainsString('Fetch URL: ' . self::$upstreamUrl, $output);
    }

    /**
     * Test fetching a remote.
     */
    public function testFetch()
    {
        $output = self::$git->fetch('upstream');
        $this->assertEmpty($output);
    }

    /**
     * Test merging a branch.
     */
    public function testCleanMerge()
    {
        $output = self::$git->merge('clean-merge', 'upstream');
        $this->assertEmpty($output);
    }

    /**
     * Test merging a branch.
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
     */
    public function testRemove()
    {
        $output = self::$git->remove('README.md');
        $this->assertEmpty($output);
    }

    /**
     * Test getting remote message.
     */
    public function testGetRemoteMessage()
    {
        $message = self::$git->getRemoteMessage('clean-merge', 'upstream');
        $this->assertEquals('Add new commit here.', $message);
    }
}
