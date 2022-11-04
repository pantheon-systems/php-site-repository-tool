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

        static::removeTypeHints($phpVersion);
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

    private static function removeTypeHints($phpVersion)
    {
        if (version_compare($phpVersion, '7.2') >= 0) {
            return;
        }

        $finder = new Finder();
        $finder->in(dirname(__DIR__) . '/src')->files()->name('*.php');

        foreach ($finder as $file) {
            print '| ' . $file->getRelativePathname() . PHP_EOL;

            $contents = $file->getContents();
            $contents = static::removeTypeHintsFromContents($contents);

            file_put_contents($file->getRealPath(), $contents);
        }
    }

    private static function removeTypeHintsFromContents($contents)
    {
        return preg_replace('#^(\w+)(public|protected|private)(\w+)(array|string|bool)(\w+[^;]+;)#', '${1}${2}${5}', $contents);
    }
}
