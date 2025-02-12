<?php declare(strict_types=1);

/* Copyright (c) 2025 - Daniel Weise <daniel.weise@concepts-and-training.de> - Extended GPL, see LICENSE */

namespace CaT\Doil\Lib\Config;

use CaT\Doil\Lib\Linux\Linux;
use CaT\Doil\Lib\SymfonyShell;
use CaT\Doil\Lib\Docker\Docker;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use CaT\Doil\Lib\Logger\LoggerFactory;
use CaT\Doil\Lib\FileSystem\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ConfigManager
{
    use SymfonyShell;

    protected const CONFIG_FILE_PATH = "/etc/doil/doil.conf";
    protected const PERSISTENT_CONFIG_FILE_PATH = "/usr/local/share/doil/.doil.conf";

    protected Filesystem $filesystem;
    protected Linux $linux;
    protected LoggerFactory $logger_factory;
    protected Docker $docker;
    protected Keycloak $keycloak;
    protected Domain $domain;

    public function __construct(
        Filesystem $filesystem,
        Linux $linux,
        LoggerFactory $logger,
        Docker $docker,
        Keycloak $keycloak,
        Domain $domain
    ) {
        $this->filesystem = $filesystem;
        $this->linux = $linux;
        $this->logger_factory = $logger;
        $this->docker = $docker;
        $this->keycloak = $keycloak;
        $this->domain = $domain;

        if (!$this->filesystem->exists(self::CONFIG_FILE_PATH)) {
            throw new \RuntimeException("Configuration file '" . self::CONFIG_FILE_PATH . "' does not exists.");
        }
    }

    public function edit(): void
    {
        $cmd = [
            "editor",
            self::CONFIG_FILE_PATH
        ];

        $logger = $this->logger_factory->getDoilLogger("ConfigManager");
        $logger->info("Edit doil configuration");
        $this->runTTY($cmd, $logger);
    }

    public function list(): void
    {
        $cmd = [
            "cat",
            self::CONFIG_FILE_PATH
        ];

        $logger = $this->logger_factory->getDoilLogger("ConfigManager");
        $logger->info("List doil configuration");
        $this->runTTY($cmd, $logger);
    }

    /**
     * @throws \Exception
     */
    public function update(InputInterface $input, OutputInterface $output, QuestionHelper $question_helper): void
    {
        $config = $this->filesystem->iniFileToArray(self::CONFIG_FILE_PATH);
        $persistent = $this->filesystem->iniFileToArray(self::PERSISTENT_CONFIG_FILE_PATH);

        $diff = array_udiff_assoc($config, $persistent, function ($a, $b) {
            if ($a === $b) {
                return 0;
            }
            return 1;
        });

        if (count($diff) === 0) {
            $output->writeln("Everything is up to date. No changes made to doil configuration.");
            return;
        }

        $diff_keycloak = array_filter($diff, function ($key) {
            if (strpos($key, "keycloak") === false) {
                return false;
            }
            return true;
        }, ARRAY_FILTER_USE_KEY);

        if (count($diff_keycloak) > 0) {
            if ($config['keycloak_enable'] || $persistent['keycloak_enable']) {
                $needs_update = true;
                if (array_key_exists("keycloak_enable", $diff_keycloak) && $diff_keycloak["keycloak_enable"]) {
                    $output->writeln("Install Keycloak");
                    $this->keycloak->installKeycloak($output, $config, $persistent);
                    $needs_update = false;
                } else if (array_key_exists("keycloak_enable", $diff_keycloak)) {
                    $output->writeln("Remove Keycloak");
                    $output->writeln("\n\t!!! This will remove all Keycloak data and configuration !!!");
                    $msg = "\tPlease confirm that you want to remove all Keycloak data and configuration [yN]:";
                    $question = new ConfirmationQuestion($msg, false);
                    if (!$question_helper->ask($input, $output, $question)) {
                        $output->writeln("\nAbort by user!");
                        $output->writeln("\n\tNo changes made to doil configuration.");
                        $output->writeln("\tPlease adjust your config.");
                        return;
                    }

                    $this->keycloak->disableKeycloak($output);
                    $needs_update = false;
                }

                if ($needs_update) {
                    $output->writeln("Update following keycloak values:");
                    $output->writeln("\t" . implode("\n\t", array_keys($diff_keycloak)));
                    $this->keycloak->installKeycloak($output, $config, $persistent);
                }
            } else {
                $output->writeln("You have changed following vars but keycloak is disabled:");
                $output->writeln("\t" . implode("\n\t", array_keys($diff_keycloak)));
                $output->writeln("\n\tNo changes made to doil configuration.");
                $output->writeln("\tPlease adjust your config.");
                return;
            }
        }

        $diff = array_filter($diff, function ($key) {
            if (strpos($key, "keycloak") === false) {
                return true;
            }
            return false;
        }, ARRAY_FILTER_USE_KEY);

        $run_once = false;
        foreach ($diff as $key => $value) {
            switch ($key) {
                case "host":
                case "https_proxy":
                    if ($run_once) {
                        break;
                    }
                    $this->domain->updateDomain($config, $persistent);
                    $run_once = true;
                    break;
                case "mail_password":
                    $output->writeln("Update Mail Password: " . $value);
                    $this->updateMailPassword($value);
                    break;
                case "global_instances_path":
                    $output->writeln("Update Global Instances Path: " . $value);
                    $this->updateGlobalInstancesPath($value);
                    break;
                default:
                    $output->writeln("Unknown key '" . $key . "'");
                    break;
            }
        }
        $this->filesystem->copy(self::CONFIG_FILE_PATH, self::PERSISTENT_CONFIG_FILE_PATH);
    }

    protected function updateHttpsProxy(mixed $value)
    {
    }

    protected function updateMailPassword(mixed $value)
    {
    }

    protected function updateGlobalInstancesPath(mixed $value)
    {
    }


}