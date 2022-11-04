<?php

/**
 * @file
 * Contains \DrupalComposerManaged\ComposerScripts.
 */

namespace PhpSiteRepositoryTool;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class ComposerScripts
{
    public static function configureForPhpVersion(Event $event)
    {
        $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        $scenario = static::determineScenario($phpVersion);
        $command = "composer scenario $scenario";
        print "> $command\n";
        passthru($command);

        $replacements = static::determineReplacementsByPhpVersion($phpVersion);
        static::applyReplacements($replacements);
    }

    private static function determineScenario($phpVersion)
    {
        switch ($phpVersion) {
            case '5.6':
            case '7.0':
                return 'php56';

            case '7.1':
            case '7.2':
            case '7.3':
                return 'default';

            case '7.4':
                return 'php74';

            case '8.0':
            case '8.1':
                return 'php80';
        }

        return 'default';
    }

    private static function determineReplacementsByPhpVersion($phpVersion)
    {
        $replacements = [];

        switch($phpVersion) {
            case '5.6':
            case '7.0':
            case '7.1':
                $r['#^(\s+)(public|protected|private)(\s+)(array|string|bool)(\s+[^;]+;)(\s*)$#m'] = '${1}${2}${5}';
        }

        return $replacements;
    }
    private static function applyReplacements($replacements)
    {
        if (empty($replacements)) {
            return;
        }

        $finder = new Finder();
        $finder->in(dirname(__DIR__) . '/src')->files()->name('*.php');

        foreach ($finder as $file) {
            print '| ' . $file->getRelativePathname() . PHP_EOL;

            $contents = $file->getContents();
            $contents = static::applyReplacementsToContents($replacements, $contents);

            file_put_contents($file->getRealPath(), $contents);
        }
    }

    private static function applyReplacementsToContents($replacements, $contents)
    {
        return preg_replace(array_keys($replacements), array_values($replacements), $contents);
    }
}
