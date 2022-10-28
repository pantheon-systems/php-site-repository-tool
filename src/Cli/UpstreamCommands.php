<?php

namespace PhpSiteRepositoryTool\Cli;

use Robo\Symfony\ConsoleIO;
use PhpSiteRepositoryTool\UpstreamManager;

class UpstreamCommands extends \Robo\Tasks
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
     * @option verbose verbose output
     */
    public function applyUpstream(ConsoleIO $io, array $options = [
        'site-repo-url' => null,
        'site-repo-branch' => null,
        'upstream-repo-url' => null,
        'upstream-repo-branch' => null,
        'strategy-option' => 'default',
        'work-dir' => null,
        'committer-name' => null,
        'committer-email' => null,
        'site' => null,
        'binding' => null,
        'bypass-sync-code' => false,
        'ff' => true,
        'clone' => true,
        'push' => true,
        'verbose' => false,
    ])
    {
        $model = new UpstreamManager();
        $result = $model->applyUpstream(
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

        $io->text('Done.');
    }
}
