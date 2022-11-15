<?php

namespace PhpSiteRepositoryTool;

use PhpSiteRepositoryTool\Utils\Git;
use PhpSiteRepositoryTool\Exceptions\NotEmptyFolderException;
use PhpSiteRepositoryTool\Exceptions\Git\GitException;
use PhpSiteRepositoryTool\Exceptions\Git\GitMergeConflictException;

/**
 * Class UpstreamManager.
 *
 * @package PhpSiteRepositoryTool
 */
class UpstreamManager
{
    /**
     * @var string
     */
    private const REMOTE = 'upstream';

    /**
     * Applies the upstream changes to the local repository.
     *
     * @param string $siteRepoUrl
     * @param string $siteRepoBranch
     * @param string $upstreamRepoUrl
     * @param string $upstreamRepoBranch
     * @param string $strategyOption
     * @param string $workdir
     * @param string $committerName
     * @param string $committerEmail
     * @param string $siteUuid
     * @param string $binding
     * @param string $updateBehavior
     * @param bool $bypassSyncCode
     * @param bool $ff
     * @param bool $clone
     * @param bool $push
     * @param bool $verbose
     *
     * @return array
     *
     * @throws GitException
     */
    public function applyUpstream(
        string $siteRepoUrl,
        string $siteRepoBranch,
        string $upstreamRepoUrl,
        string $upstreamRepoBranch,
        string $strategyOption,
        string $workdir,
        string $committerName,
        string $committerEmail,
        string $siteUuid,
        string $binding,
        string $updateBehavior,
        bool $bypassSyncCode,
        bool $ff,
        bool $clone,
        bool $push,
        bool $verbose
    ): array {
        $git = new Git(
            $committerName,
            $committerEmail,
            $workdir,
            $verbose,
            $siteUuid,
            $binding,
            $bypassSyncCode
        );

        $result = [
            'clone' => false,
            'pull' => false,
            'push' => false,
            'conflicts' => '',
            'errormessage' => '',
        ];

        if ($clone) {
            try {
                $git->cloneRepository($siteRepoUrl, $siteRepoBranch);
                $result['clone'] = true;
                $result['logs'][] = 'Repository has been cloned';
            } catch (NotEmptyFolderException $e) {
                $result['errormessage'] = sprintf("Workdir '%s' is not empty.", $workdir);
                return $result;
            } catch (GitException $e) {
                $result['errormessage'] = sprintf("Error cloning site repo: %s", $e->getMessage());
                return $result;
            }
        }

        try {
            $git->remoteAdd(self::REMOTE, $upstreamRepoUrl);
            $result['logs'][] = 'Upstream remote has been added';
            $git->fetch(self::REMOTE);
            $result['logs'][] = 'Updates have been fetched';
        } catch (GitException $e) {
            $result['errormessage'] = sprintf("Could not fetch upstream. Check that your upstream's git repository is accessible and that Pantheon has any required access tokens: %s", $e->getMessage());
            return $result;
        }

        if ('procedural' === $updateBehavior) {
            if (!$git->isLatestChangeMatchesRemote($this->getOffSwitchPaths(), self::REMOTE, $upstreamRepoBranch)) {
                // An unmerged off-switch file change found, immediately return the result with the success flag.
                $result['pull'] = true;
                $result['logs'][] = 'An unmerged off-switch update found';
                return $result;
            }
        }

        $commitMessages = [
            $git->getRemoteMessage($upstreamRepoBranch),
            sprintf('Was: Merged %s into %s.', $upstreamRepoBranch, $siteRepoBranch),
        ];
        $commitAuthor = '';
        try {
            $git->merge(
                $upstreamRepoBranch,
                self::REMOTE,
                $strategyOption,
                !$ff
            );
            $result['logs'][] = 'Updates have been merged';
        } catch (GitMergeConflictException $e) {
            // WordPress License handling stuff.
            $unmergedFiles = $git->listUnmergedFiles();
            if (!$this->allUnmergedFilesInAllowList($unmergedFiles)) {
                $result['conflicts'] = $unmergedFiles;
                $result['errormessage'] = sprintf("Merge conflict: %s", $e->getMessage());
                return $result;
            }

            foreach ($unmergedFiles as $file) {
                $git->remove([$file]);
            }

            $commitMessages = [
                $git->getRemoteMessage($upstreamRepoBranch),
                'System automatically resolved merge conflict, for more information see:',
                'https://pantheon.io/docs/start-states/wordpress#20220524-1',
            ];
            $commitAuthor = 'Pantheon Automation <bot@getpantheon.com>';
        }

        try {
            if ($git->isAnythingToCommit()) {
                $git->commit($commitMessages, $commitAuthor);
                $result['logs'][] = 'Updates have been committed';
            }
        } catch (GitException $e) {
            $result['errormessage'] = sprintf("Error committing to git: %s", $e->getMessage());
            return $result;
        }

        $result['pull'] = true;

        if ($push) {
            try {
                $git->pushAll();
                $result['push'] = true;
                $result['operations'][] = 'Updates have been pushed';
            } catch (GitException $e) {
                $result['errormessage'] = sprintf("Error during git push: %s", $e->getMessage());
                return $result;
            }
        }

        return $result;
    }

    /**
     * Checks if all unmerged files are in the allow list.
     *
     * @param array $unmergedFiles
     *   The list of unmerged files.
     *
     * @return bool
     *   TRUE if all unmerged files are in the allow list, FALSE otherwise.
     */
    private function allUnmergedFilesInAllowList(array $unmergedFiles): bool
    {
        if (empty($unmergedFiles)) {
            return false;
        }

        $allowPattern = '/wp-content\/themes\/.*\/LICENSE\.md/';
        foreach ($unmergedFiles as $file) {
            if (!preg_match($allowPattern, $file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return list of "Off Switch" path patterns.
     *
     * An "Off Switch" file serves as a flag to prevent the dashboard from automatically displaying an update,
     * because it modifies composer.json and will likely cause merge conflicts.
     *
     * @return string[]
     */
    private function getOffSwitchPaths(): array
    {
        return [
            'upstream-configuration/off-switches/*',
        ];
    }
}
