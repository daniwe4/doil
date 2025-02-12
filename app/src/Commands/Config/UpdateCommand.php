<?php

declare(strict_types=1);

/* Copyright (c) 2025 - Daniel Weise <daniel.weise@concepts-and-training.de> - Extended GPL, see LICENSE */

namespace CaT\Doil\Commands\Config;

use CaT\Doil\Lib\Posix\Posix;
use CaT\Doil\Lib\ConsoleOutput\Writer;
use CaT\Doil\Lib\Config\ConfigManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends Command
{
    protected static $defaultName = "config:update";
    protected static $defaultDescription = "<fg=red>!NEEDS SUDO PRIVILEGES!</> Updates the system with the doil config.";

    protected Posix $posix;
    protected ConfigManager $config_manager;
    protected Writer $writer;

    public function __construct(
        Posix $posix,
        ConfigManager $config_manager,
        Writer $writer
    ) {
        parent::__construct();

        $this->posix = $posix;
        $this->config_manager = $config_manager;
        $this->writer = $writer;
    }

    public function execute(InputInterface $input, OutputInterface $output) : int
    {
        if (! $this->posix->isSudo()) {
            $this->writer->error(
                $output,
                "Please execute this script as sudo user!"
            );
            return Command::FAILURE;
        }

//        $this->writer->beginBlock($output, "Update configuration");
        $this->config_manager->update($input, $output, $this->getHelper("question"));
//        $this->writer->endBlock();

        return Command::SUCCESS;
    }
}