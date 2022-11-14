<?php

namespace PhpSiteRepositoryTool\Utils;

use PhpSiteRepositoryTool\Exceptions\Git\GitException;
use PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException;
use PhpSiteRepositoryTool\Exceptions\NotEmptyFolderException;
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
     * @param string $committerName
     * @param string $committerEmail
     * @param string $workdir
     * @param bool $verbose
     * @param string $siteUuid
     * @param string $binding
     * @param bool $bypassSyncCode
     */
    public function __construct(string $committerName = '', string $committerEmail = '', string $workdir = '', bool $verbose = false, string $siteUuid = '', string $binding = '', bool $bypassSyncCode = false)
    {
        $this->workdirCreated = false;

        if ($workdir and is_dir($workdir)) {
            $this->workdirCreated = true;
        }

        $this->workdir = $workdir;

        $this->env = [];
        $this->verbose = $verbose;

        if ($committerName) {
            $this->env['GIT_AUTHOR_NAME'] = $committerName;
            $this->env['GIT_COMMITTER_NAME'] = $committerName;
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
     * @return string
     *
     * @throws GitException
     */
    public function fetch(string $remoteName): string
    {
        return $this->execute(['fetch', $remoteName]);
    }

    /**
     * Performs merge operation.
     *
     * @param string $branchName
     * @param string $remoteName
     * @param string $strategyOption
     * @param bool $noFf
     *
     * @return string
     *
     * @throws GitException
     * @throws GitMergeConflictException
     */
    public function merge(string $branchName, string $remoteName = 'origin', string $strategyOption = '', bool $noFf = false): string
    {
        $command = ['merge', sprintf('%s/%s', $remoteName, $branchName), '--no-commit', '--quiet'];
        if ($strategyOption) {
            $command[] = '-X';
            $command[] = $strategyOption;
        }
        if ($noFf) {
            $command[] = '--no-ff';
        }

        $process = $this->executeAndReturnProcess($command);
        if (0 === $process->getExitCode()) {
            return $process->getOutput();
        }

        $outputLines = explode("\n", $process->getOutput());
        $conflicts = [];
        foreach ($outputLines as $line) {
            if (strpos($line, 'CONFLICT') === 0) {
                $conflicts[] = $line;
            }
        }
        if ($conflicts) {
            throw new GitMergeConflictException(
                sprintf("Merge conflict detected:\n%s", implode("\n", $conflicts))
            );
        }

        throw new GitException(
            sprintf("Merge failed:\n%s", $process->getErrorOutput())
        );
    }

    /**
     * List all the unmerged files.
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
     * @param array $files
     *
     * @return string
     *
     * @throws GitException
     */
    public function remove(array $files): string
    {
        return $this->execute(array_merge(['rm'], $files));
    }

    /**
     * Get commit message from the last commit on remote.
     *
     * @param string $branchName
     * @param string $remoteName
     *
     * @return string
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
     * Returns TRUE is there is anything to commit.
     *
     * @return bool
     *
     * @throws \PhpSiteRepositoryTool\Exceptions\Git\GitException
     */
    public function isAnythingToCommit(): bool
    {
        return '' !== $this->execute(['status', '--porcelain']);
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
    public function execute(array $command): string
    {
        try {
            $process = $this->executeAndReturnProcess($command);
            if ($this->verbose) {
                printf("[RET] %s\n", $process->getExitCode());
                printf("[OUT] %s\n", $process->getOutput());
                printf("[ERR] %s\n", $process->getErrorOutput());
            }
        } catch (Throwable $t) {
            throw new GitException(
                sprintf('Failed executing Git command: %s', $t->getMessage())
            );
        }

        if (0 !== $process->getExitCode()) {
            throw new GitException(
                sprintf(
                    'Git command failed with exit code %d and message %s',
                    $process->getExitCode(),
                    $process->getErrorOutput()
                )
            );
        }

        return $process->getOutput();
    }

    /**
     * Executes the Git command and return the process object.
     *
     * @param array|string $command
     *
     * @return \PhpSiteRepositoryTool\Utils\Process
     */
    private function executeAndReturnProcess(array $command): Process
    {
        if ($this->verbose) {
            printf("RUN: git %s\n", implode(" ", $command));
        }
        $process = new Process(array_merge(['git'], $command), $this->workdir, $this->env);
        $process->run();
        return $process;
    }
}
