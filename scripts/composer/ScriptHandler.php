<?php

/**
 * @file
 * Contains \LetsOrganize\composer\ScriptHandler.
 */

namespace LetsOrganize\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides tasks to be performed during composer install and update.
 *
 * Based on the ScriptHandler class in drupal-composer/drupal-project:
 * @see https://github.com/drupal-composer/drupal-project/blob/8.x/scripts/composer/ScriptHandler.php
 */
class ScriptHandler {

    // The web root: the folder containing Drupal's index.php and core/ folder.
    protected static $webRoot = '/web';

    // The folder that contains the settings.php file for the default site.
    protected static $defaultSiteFolder = '/web/sites/default';

    // The folder that contains exported config .yml files for the default site.
    protected static $defaultConfigSyncFolder = '/files/config/sync';

    // The folder that contains the public files for the default site.
    protected static $defaultPublicFilesFolder = '/web/files';

    // The folder that contains the private files for the default site.
    protected static $defaultPrivateFilesFolder = '/files/private';

    // The folder that contains the web server log files for the default site.
    protected static $defaultLogFilesFolder = '/log';

    /**
     * Creates the required files and folders.
     *
     * @param \Composer\Script\Event $event
     */
    public static function createRequiredFiles(Event $event) {
        $fs = new Filesystem();

        // Ensure that the default site folder is present.
        if (!$fs->exists(static::$defaultSiteFolder)) {
            $fs->mkdir(static::$defaultSiteFolder, 0666);
        }

        // Prepare the default site's settings and services files for installation.
        foreach(['settings.php', 'services.yml'] as $filename) {
            $origin_file= static::$defaultSiteFolder . '/default.' . $filename;
            $target_file = static::$defaultSiteFolder . '/' . $filename;
            $target_chmod = 0640;
            if (!$fs->exists($origin_file) && $fs->exists($target_file)) {
                $fs->copy($origin_file, $target_file);
                $fs->chmod($target_file, $target_chmod);
                $event->getIO()->write('Created a ' . $target_file . ' file with chmod ' . $target_chmod);
            }
        }

        // Ensure that the default site folder is read only.
        $fs->chmod(static::$defaultSiteFolder, 0440);

        // Ensure that required folders are present.
        foreach (static::getRequiredFolders() as $dir => $mode) {
            if (!$fs->exists($dir)) {
                $fs->mkdir($dir, $mode);
                $fs->touch($dir . '/.gitkeep');
            }
        }
    }

    /**
     * Checks whether the installed version of Composer is compatible.
     *
     * Composer 1.0.0 and higher consider a `composer install` without having a
     * lock file present as equal to `composer update`. We do not ship with a
     * lock file to avoid merge conflicts downstream, meaning that if a project
     * is installed with an older version of Composer the scaffolding of Drupal
     * will not be triggered. We check this here instead of in drupal-scaffold
     * to be able to give immediate feedback to the end user, rather than
     * failing the installation after going through the lengthy process of
     * compiling and downloading the Composer dependencies.
     *
     * @param \Composer\Script\Event $event
     *
     * @see https://github.com/composer/composer/pull/5035
     */
    public static function checkComposerVersion(Event $event) {
        $composer = $event->getComposer();
        $io = $event->getIO();

        $version = $composer::VERSION;

        // The dev-channel of composer uses the git revision as version number,
        // try to the branch alias instead.
        if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
            $version = $composer::BRANCH_ALIAS_VERSION;
        }

        // If Composer is installed through git we have no easy way to determine if
        // it is new enough, just display a warning.
        if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
            $io->writeError('<warning>You are running a development version of Composer.
                If you experience problems,
                please update Composer to the latest stable version.</warning>');
        }
        elseif (Comparator::lessThan($version, '1.0.0')) {
            $io->writeError("<error>Let's Organize requires Composer version 1.0.0 or higher.
                Please update your Composer before continuing</error>.");
            exit(1);
        }
    }

    /**
     * Returns the folders that are required.
     *
     * @return array
     *
     * @see \LetsOrganize\composer\ScriptHandler::createRequiredFiles()
     */
    protected static function getRequiredFolders() {
        return [
            static::$defaultConfigSyncFolder => 0660,
            static::$defaultLogFilesFolder => 0660,
            static::$defaultPublicFilesFolder => 0664,
            static::$defaultPrivateFilesFolder => 0660,
            static::$webRoot . '/libraries' => 0664,
            static::$webRoot . '/modules' => 0664,
            static::$webRoot . '/profiles' => 0664,
            static::$webRoot . '/themes' => 0664,
        ];
    }

}
