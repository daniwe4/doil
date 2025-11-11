<?php

declare(strict_types=1);

/* Copyright (c) 2025 - Daniel Weise <daniel.weise@concepts-and-training.de> - Extended GPL, see LICENSE */

namespace CaT\Doil\Setup\FileStructure;

use CaT\Doil\Lib\FileSystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

class BaseDirectories
{
    protected const USER_PATHS = [
        ".doil/config",
        ".doil/repositories",
        ".doil/instances"
    ];

    protected const PATHS = [
        "/usr/local/lib/doil",
        "/usr/local/lib/doil/server",
        "/usr/local/lib/doil/server/php",
        "/usr/local/share/doil",
        "/usr/local/share/doil/templates",
    ];

    protected const SUPER_BIT_PATHS = [
        "/etc/doil",
        "/srv/instances",
        "/usr/local/lib/doil/app",
        "/usr/local/share/doil/instances",
        "/usr/local/share/doil/repositories",
        "/var/log/doil",
    ];

    protected string $home_dir;
    protected string $user_name;

    public function __construct(
        protected FileSystem $fileSystemShell,
        string $home_dir,
        string $user_name,
    ) {
        $this->home_dir = $home_dir;
        $this->user_name = $user_name;
    }

    public function createBaseDirs() : void
    {
        foreach (self::USER_PATHS as $path) {
            if (! $this->fileSystemShell->makeDirectoryRecursive($this->home_dir . "/" . $path)) {
                throw new IOException("Unable to create directory " . $this->home_dir . "/" . $path);
            }
        }

        foreach (self::PATHS as $path) {
            if (! $this->fileSystemShell->makeDirectoryRecursive($path)) {
                throw new IOException("Unable to create directory " . $path);
            }
        }

        foreach (self::SUPER_BIT_PATHS as $path) {
            if (! $this->fileSystemShell->makeDirectoryRecursive($path)) {
                throw new IOException("Unable to create directory " . $path);
            }
        }
    }

    public function setOwnerGroupForBaseDirs() : void
    {
        $this->fileSystemShell->chownRecursive($this->home_dir . "/.doil", $this->user_name, $this->user_name);

        foreach (self::PATHS as $path) {
            $this->fileSystemShell->chownRecursive($path, "root", "doil");
        }

        foreach (self::SUPER_BIT_PATHS as $path) {
            $this->fileSystemShell->chownRecursive($path, "root", "doil");
        }
    }

    public function setFilePermissionsForBaseDirs() : void
    {
        $this->fileSystemShell->chmod($this->home_dir . "/.doil", 0775);

        foreach (self::PATHS as $path) {
            $this->fileSystemShell->chmod($path, 0775);
        }

        foreach (self::SUPER_BIT_PATHS as $path) {
            $this->fileSystemShell->chmod($path, 02775);
        }
    }

    // TODO: kann evtl. weg
//    public function deleteNonUserDirs(bool $delete_global_instances_path) : void
//    {
//        foreach (self::PATHS as $path) {
//            if ($path === "/usr/local/share/doil") {
//                continue;
//            }
//            $this->fileSystemShell->remove($path);
//        }
//
//        foreach (self::SUPER_BIT_PATHS as $path) {
//            if ($path === "/srv/instances" && ! $delete_global_instances_path) {
//                continue;
//            }
//            $this->fileSystemShell->remove($path);
//        }
//    }
//
//    public function deleteUserDirs() : void
//    {
//        $this->fileSystemShell->remove($this->home_dir . "/.doil");
//    }
}