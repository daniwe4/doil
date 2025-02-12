<?php declare(strict_types=1);

/* Copyright (c) 2025 - Daniel Weise <daniel.weise@concepts-and-training.de> - Extended GPL, see LICENSE */

namespace CaT\Doil\Lib\Config;

use CaT\Doil\Lib\Linux\Linux;
use CaT\Doil\Lib\Posix\Posix;
use CaT\Doil\Lib\Docker\Docker;
use CaT\Doil\Lib\ConsoleOutput\Writer;
use CaT\Doil\Lib\FileSystem\Filesystem;

class Domain
{
    protected const PATH_GLOBAL_INSTANCES = "/usr/local/share/doil/instances";
    protected const PATH_LOCAL_INSTANCES = ".doil/instances";

    protected Filesystem $filesystem;
    protected Linux $linux;
    protected Writer $writer;
    protected Docker $docker;
    protected Posix $posix;

    public function __construct(Filesystem $filesystem, Linux $linux, Writer $writer, Docker $docker, Posix $posix)
    {
        $this->filesystem = $filesystem;
        $this->linux = $linux;
        $this->writer = $writer;
        $this->docker = $docker;
        $this->posix = $posix;
    }

    public function updateDomain(array $config, array $persistent)
    {
        $persistent_http_scheme = "http://";
        if ($persistent['https_proxy'] == "true") {
            $persistent_http_scheme = "https://";
        }

        $http_scheme = "http://";
        if ($config['https_proxy'] == "true") {
            $http_scheme = "https://";
        }

        $home_dir = $this->posix->getHomeDirectory($this->posix->getUserId());
        $global_instances = $this->filesystem->getFilesInPath(self::PATH_GLOBAL_INSTANCES);
        $local_instances = $this->filesystem->getFilesInPath($home_dir . "/" . self::PATH_LOCAL_INSTANCES);

        foreach ($global_instances as $instance) {
            if (!$this->docker->isInstanceUp(self::PATH_GLOBAL_INSTANCES . "/" . $instance)) {
                $this->docker->startContainerByDockerCompose(self::PATH_GLOBAL_INSTANCES . "/" . $instance);
            }
            $this->docker->executeBashCommandInsideContainer(
                $instance . "_global",
                "/var/ilias/data",
                "sed -i 's%${persistent_http_scheme}${persistent['host']}/${instance}%${http_scheme}${config['host']}/${instance}%g' ilias-config.json"
            );
        }

        foreach ($local_instances as $instance) {
            if ($instance != "i10test") {
                continue;
            }
            $path = $home_dir . "/" . self::PATH_LOCAL_INSTANCES . "/" . $instance;
            if (!$this->docker->isInstanceUp($path)) {
                $this->docker->startContainerByDockerCompose($path);
            }
            $this->docker->executeBashCommandInsideContainer(
                $instance . "_local",
                "/var/ilias/data",
                "sed -i 's%${persistent_http_scheme}${persistent['host']}/${instance}%${http_scheme}${config['host']}/${instance}%g' ilias-config.json"
            );

            if ($config['https_proxy'] == "true") {
                $this->docker->applyState($instance . ".local", "enable-https");
            } else {
                $this->docker->applyState($instance . ".local", "disable-https");
            }
        }
        //die("ende");

        // Ilias Instanzen anpassen ilias_config.json + update (suche nach doil_domain)
        // $this->docker->setGrain($instance_salt_name, "doil_domain", $http_scheme . $host . "/" . $options["name"]);
        //   für jede Ilias Instanz
        // apache state für jede instanz
        // wenn saml aktiv -> deaktivieren und neu aktivieren

        // Proxy anpassen
        // /usr/local/lib/doil/server/proxy/conf/nginx/local.conf server_name anpassen

        // Mail anpassen

        // Keycloak anpassen
    }
}