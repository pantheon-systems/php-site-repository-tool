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
     * @option clone clone the upstream repository
     * @option push push the changes to the remote repository
     * @option work-dir working directory
     * @option site-repo-url site repository url
     * @option site-repo-branch site repository branch
     * @option upstream-repo-url upstream repository url
     * @option upstream-repo-branch upstream repository branch
     * @option ff do not use fast-forward
     * @option strategy-option strategy top use for this command
     */
    public function applyUpstream(ConsoleIO $io, array $options = [
        'clone' => true,
        'push' => true,
        'work-dir' => null,
        'site-repo-url' => null,
        'site-repo-branch' => null,
        'upstream-repo-url' => null,
        'upstream-repo-branch' => null,
        'ff' => true,
        'strategy-option' => 'default',
    ])
    {
        $model = new UpstreamManager(2);
        $result = $model->multiply(4);

        $io->text('Done.');
    }
}
