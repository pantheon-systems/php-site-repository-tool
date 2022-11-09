<?php

namespace PhpSiteRepositoryTool\Cli;

use PhpSiteRepositoryTool\UpstreamManager;
use PhpSiteRepositoryTool\EnvironmentMergeManager;
use Robo\Tasks;

/**
 * Class SiteRepositoryCommands.
 *
 * @package PhpSiteRepositoryTool\Cli
 */
class SiteRepositoryCommands extends Tasks
{
    /**
     * Apply upstream command.
     *
     * @command apply_upstream
     *
     * @option site-repo-url site repository url
     * @option site-repo-branch site repository branch
     * @option upstream-repo-url upstream repository url
     * @option upstream-repo-branch upstream repository branch
     * @option strategy-option strategy top use for this command
     * @option work-dir working directory
     * @option committer-name committer name
     * @option committer-email committer email
     * @option site site uuid
     * @option binding binding uuid
     * @option update-behavior Either heirloom or procedural
     * @option bypass-sync-code bypass sync code
     * @option ff use fast-forward (also supported: --no-ff)
     * @option clone clone the upstream repository (also supported: --no-clone)
     * @option push push the changes to the remote repository (also supported: --no-push)
     *
     * @param array $options
     *
     * @return array
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function applyUpstream(array $options = [
        'site-repo-url' => '',
        'site-repo-branch' => '',
        'upstream-repo-url' => '',
        'upstream-repo-branch' => '',
        'strategy-option' => '',
        'work-dir' => '',
        'committer-name' => '',
        'committer-email' => '',
        'site' => '',
        'binding' => '',
        'update-behavior' => 'heirloom',
        'bypass-sync-code' => false,
        'ff' => true,
        'clone' => true,
        'push' => true,
        'format' => 'json'
    ]): array
    {
        $upstreamManager = new UpstreamManager();
        return $upstreamManager->applyUpstream(
            $options['site-repo-url'],
            $options['site-repo-branch'],
            $options['upstream-repo-url'],
            $options['upstream-repo-branch'],
            $options['strategy-option'],
            $options['work-dir'],
            $options['committer-name'],
            $options['committer-email'],
            $options['site'],
            $options['binding'],
            $options['update-behavior'],
            $options['bypass-sync-code'],
            $options['ff'],
            $options['clone'],
            $options['push'],
            $options['verbose']
        );
    }

    /**
     * Merge Environment command.
     *
     * @command merge_environment
     *
     * @option site-repo-url site repository url
     * @option from-branch branch to merge from
     * @option to-branch branch to merge to
     * @option strategy-option strategy top use for this command
     * @option work-dir working directory
     * @option committer-name committer name
     * @option committer-email committer email
     * @option site site uuid
     * @option binding binding uuid
     * @option bypass-sync-code bypass sync code
     * @option ff use fast-forward (also supported: --no-ff)
     * @option push push the changes to the remote repository (also supported: --no-push)
     *
     * @param array $options
     *
     * @return array
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function mergeEnvironment(
        array $options = [
            'site-repo-url' => '',
            'site-repo-branch' => '',
            'from-branch' => '',
            'to-branch' => '',
            'strategy-option' => '',
            'work-dir' => '',
            'committer-name' => '',
            'committer-email' => '',
            'site' => '',
            'binding' => '',
            'bypass-sync-code' => false,
            'ff' => true,
            'push' => true,
            'format' => 'json'
        ]
    ): array {
        $environmentMergeManager = new EnvironmentMergeManager();
        return $environmentMergeManager->mergeEnvironment(
            $options['site-repo-url'],
            $options['site-repo-branch'],
            $options['from-branch'],
            $options['to-branch'],
            $options['strategy-option'],
            $options['work-dir'],
            $options['committer-name'],
            $options['committer-email'],
            $options['site'],
            $options['binding'],
            $options['bypass-sync-code'],
            $options['ff'],
            $options['push'],
            $options['verbose']
        );
    }
}
