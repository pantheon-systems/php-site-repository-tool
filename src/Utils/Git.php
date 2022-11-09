<?php

namespace PhpSiteRepositoryTool\Utils;

use PhpSiteRepositoryTool\Exceptions\Git\GitException;
use PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException;
use PhpSiteRepositoryTool\Exceptions\Git\GitNoDiffException;
use PhpSiteRepositoryTool\Exceptions\NotEmptyFolderException;
use PhpSiteRepositoryTool\Utils\Process;
use Throwable;

/**
 * Class Git.
 */
class Git
{
    /**
     * @var string
     */
    private string $workdir;

    private array $env;

    private bool $verbose;

    private bool $workdirCreated;

    /**
     * Git constructor.
     *
     * @param string $workdir
     *   The path to the repository.
     * @param bool $skipValidation
     *   Skip git status validation.
     *
     * @throws \Pantheon\Terminus\Exceptions\TerminusException
     */
    public function __construct(string $commiterName = '', string $committerEmail = '', string $workdir = '', bool $verbose = false, string $siteUuid = '', string $binding = '', bool $bypassSyncCode = false)
    {
        $this->workdirCreated = false;

        if ($workdir and is_dir($workdir)) {
            $this->workdirCreated = true;
        }

        $this->workdir = $workdir;

        $this->env = [];
        $this->verbose = $verbose;

        if ($commiterName) {
            $this->env['GIT_AUTHOR_NAME'] = $commiterName;
            $this->env['GIT_COMMITTER_NAME'] = $commiterName;
        }

        if ($committerEmail) {
            $this->env['GIT_AUTHOR_EMAIL'] = $committerEmail;
            $this->env['GIT_COMMITTER_EMAIL'] = $committerEmail;
        }

        if ($siteUuid) {
            // @todo Is it really used?
            $this->env['GL_PROJECT'] = $siteUuid;
        }

        if ($binding) {
            $this->env['USER'] = $binding;
        }

        if ($bypassSyncCode) {
            $this->env['BYPASS_SYNC_CODE'] = '1';
        }

        // @todo Confirm the following line's comment.
        // Allow modifications to pantheon.upstream.yml file without warnings.
        $this->env['GL_BYPASS_UPDATE_HOOK'] = '1';
    }

    /**
     * Clone a remote repository.
     *
     * @param string $repoUrl
     * @param string $branchName
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     * @throws \PhpSiteRepositoryTool\Exceptions\NotEmptyFolderException
     */
    public function cloneRepository(string $repoUrl, string $branchName): void
    {
        // Use 2 here because: "." and "..".
        if (count(scandir($this->workdir)) > 2) {
            // Not empty dir, throw exception.
            throw new NotEmptyFolderException(sprintf("The folder '%s' is not empty.", $this->workdir));
        }
        $this->execute(['clone', '-b', $branchName, $repoUrl, $this->workdir]);
    }

    /**
     * Adds remote.
     *
     * @param string $name
     * @param string $remoteUrl
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function remoteAdd(string $name, string $remoteUrl): void
    {
        $this->execute(['remote', 'add', $name, $remoteUrl]);
    }

    /**
     * Fetches from the remote.
     *
     * @param string $remoteName
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function fetch(string $remoteName): void
    {
        $this->execute(['fetch', $remoteName]);
    }

    /**
     * Performs merge operation.
     *
     * @param array $options
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException
     */
    public function merge(string $branchName, string $remoteName = 'origin', string $strategyOption = '', bool $noFf = false): void
    {
        $options = [];
        if ($strategyOption) {
            $options += ['-X', $strategyOption];
        }
        if ($noFf) {
            $options += ['--no-ff'];
        }
        $process = $this->executeAndReturnProcess(array_merge(['merge', '--no-commit', '--quiet'], $options, [sprintf('%s/%s', $remoteName, $branchName)]));
        if ($process->getExitCode()) {
            $output = $process->getOutput();
            $outputLines = explode("\n", $output);
            $conflicts = [];
            foreach ($outputLines as $line) {
                if (strpos($line, 'CONFLICT') === 0) {
                    $conflicts[] = $line;
                }
            }
            if ($conflicts) {
                throw new GitMergeConflictException(
                    sprintf("Merge conflict detected:\n%s", implode("\n", $conflicts)),
                    $process->getExitCode()
                );
            } else {
                throw new GitException(
                    sprintf("Merge failed:\n%s", $process->getErrorOutput()),
                    $process->getExitCode()
                );
            }
        }
    }

    /**
     * List all of the unmerged files.
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function listUnmergedFiles(): array
    {
        $output = $this->execute(['ls-files', '--unmerged']);
        $lines = explode("\n", $output);
        $files = [];
        foreach ($lines as $line) {
            // Skip blank lines.
            if (!$line) {
                continue;
            }
            // Lines are like this:
            // 100644 8026076649ceccbe96a6292f2432652f08483035 1 wp-content/themes/twentytwentytwo/assets/fonts/source-serif-pro/LICENSE.md
            $components = explode("\t", $line);
            $files[] = end($components);
        }
        return array_unique($files);
    }

    /**
     * Removes files.
     *
     * @param array $options
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function remove(array $files): void
    {
        $this->execute(array_merge(['rm'], $files));
    }

    /**
     * Get commit message from the last commit on remote.
     *
     * @param string $branchName
     * @param string $remoteName
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function getRemoteMessage(string $branchName, string $remoteName = 'upstream'): string
    {
        $commitHash = trim($this->execute(['rev-parse', sprintf('refs/remotes/%s/%s', $remoteName, $branchName)]));
        return trim($this->execute(['log', '--format="%B"', '-n', '1', $commitHash]), "\n\"");
    }

    /**
     * Commits the changes.
     *
     * @param array $commitMessages
     *   The commit messages.
     * @param string $author
     *   The commit author.
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function commit(array $commitMessages, string $author = ''): void
    {
        $options = [];
        foreach ($commitMessages as $message) {
            $options = array_merge($options, ['-m', $message]);
        }

        if ($author) {
            $options = array_merge($options, ['--author', $author]);
        }

        $this->execute(array_merge(['commit'], $options));
    }

    /**
     * Performs push of everything.
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function pushAll(): void
    {
        $this->execute(['push', '--all']);
    }

    /**
     * Return workdir path.
     */
    public function getWorkdir(): string
    {
        if (!$this->workdirCreated) {
            $this->createWorkdir();
        }
        return $this->workdir;
    }

    /**
     * Creates workdir.
     */
    protected function createWorkdir(): void
    {
        if (!is_dir($this->workdir)) {
            if ($this->verbose) {
                printf("RUN: mkdir '%s'.\n", $this->workdir);
            }
            mkdir($this->workdir, 0755);
        }
        $this->workdirCreated = true;
    }

    /**
     * Executes the Git command.
     *
     * @param array $command
     *
     * @return string
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    private function execute(array $command): string
    {
        $input = null;
        try {
            $process = $this->executeAndReturnProcess($command);
            if ($this->verbose) {
                printf("[RET] %s\n", $process->getExitCode());
                printf("[OUT] %s\n", $process->getOutput());
                printf("[ERR] %s\n", $process->getErrorOutput());
            }
            if (0 !== $process->getExitCode()) {
                throw new GitException(
                    sprintf(
                        'Git command failed with exit code %d and message %s',
                        $process->getExitCode(),
                        $process->getErrorOutput()
                    ),
                    $process->getExitCode()
                );
            }
        } catch (Throwable $t) {
            throw new GitException(
                sprintf('Failed executing Git command: %s', $t->getMessage()),
                $process->getExitCode()
            );
        }

        return $process->getOutput();
    }

    /**
     * Executes the Git command and return the process object.
     *
     * @param array|string $command
     * @param null|string $input
     *
     * @return PhpSiteRepositoryTool\Utils\Process
     */
    private function executeAndReturnProcess(array $command): Process
    {
        $input = null;
        if ($this->verbose) {
            printf("RUN: git %s\n", implode(" ", $command));
        }
        $process = new Process(array_merge(['git'], $command), $this->workdir, $this->env, $input, 180);
        $process->run();
        return $process;
    }
}
