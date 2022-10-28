<?php

namespace PhpSiteRepositoryTool\Cli;

use Robo\Symfony\ConsoleIO;
use PhpSiteRepositoryTool\UpstreamManager;
use PhpSiteRepositoryTool\EnvironmentMergeManager;

class SiteRepositoryCommands extends \Robo\Tasks
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
     * @option bypass-sync-code bypass sync code
     * @option ff use fast-forward (also supported: --no-ff)
     * @option clone clone the upstream repository (also supported: --no-clone)
     * @option push push the changes to the remote repository (also supported: --no-push)
     */
    public function applyUpstream(ConsoleIO $io, array $options = [
        'site-repo-url' => '',
        'site-repo-branch' => '',
        'upstream-repo-url' => '',
        'upstream-repo-branch' => '',
        'strategy-option' => 'default',
        'work-dir' => '',
        'committer-name' => '',
        'committer-email' => '',
        'site' => '',
        'binding' => '',
        'bypass-sync-code' => false,
        'ff' => true,
        'clone' => true,
        'push' => true
    ])
    {
        $upstreamManager = new UpstreamManager();
        $result = $upstreamManager->applyUpstream(
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
            $options['bypass-sync-code'],
            $options['ff'],
            $options['clone'],
            $options['push'],
            $options['verbose']
        );

        $io->write(json_encode($result));
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
     */
    public function mergeEnvironment(ConsoleIO $io, array $options = [
        'site-repo-url' => '',
        'site-repo-branch' => '',
        'from-branch' => '',
        'to-branch' => '',
        'strategy-option' => 'default',
        'work-dir' => '',
        'committer-name' => '',
        'committer-email' => '',
        'site' => '',
        'binding' => '',
        'bypass-sync-code' => false,
        'ff' => true
    ])
    {
        $environmentMergeManager = new EnvironmentMergeManager();
        $result = $environmentMergeManager->mergeEnvironment(
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
            $options['verbose']
        );

        $io->write(json_encode($result));
    }
}
