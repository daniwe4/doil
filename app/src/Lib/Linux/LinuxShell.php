<?php declare(strict_types=1);

/* Copyright (c) 2022 - Daniel Weise <daniel.weise@concepts-and-training.de> - Extended GPL, see LICENSE */

namespace CaT\Doil\Lib\Linux;

use Psr\Log\LoggerInterface;
use CaT\Doil\Lib\SymfonyShell;
use CaT\Doil\Lib\Logger\LoggerFactory;

class LinuxShell implements Linux
{
    use SymfonyShell;

    protected LoggerInterface $logger;

    public function __construct(LoggerFactory $logger)
    {
        $this->logger = $logger->getDoilLogger("LINUX");
    }

    public function addUserToGroup(string $user, string $group) : void
    {
        $cmd = [
            "usermod",
            "-a",
            "-G",
            $group,
            $user
        ];

        $this->logger->info("Add user '$user' to group '$group'");
        $this->run($cmd, $this->logger);
    }

    public function removeUserFromGroup(string $user, string $group) : void
    {
        $cmd = [
            "deluser",
            $user,
            $group
        ];

        $this->logger->info("Remove user '$user' from group '$group'");
        $this->run($cmd, $this->logger);
    }

    public function addGroup(string $name) : void
    {
        $cmd = [
            "groupadd",
            $name
        ];
        $this->logger->info("Add group '$name'");
        $this->run($cmd, $this->logger);
    }

    public function deleteGroup(string $name) : void
    {
        $cmd = [
            "groupdel",
            $name
        ];

        $this->logger->info("Delete group '$name'");
        $this->run($cmd, $this->logger);
    }

    public function groupExists(string $name) : bool
    {
        $cmd = [
            "grep",
            "-E",
            "^" . $name . ":",
            "/etc/group"
        ];

        $this->logger->info("Get group '$name'");
        try {
            $this->run($cmd, $this->logger);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}