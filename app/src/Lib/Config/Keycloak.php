<?php declare(strict_types=1);

/* Copyright (c) 2025 - Daniel Weise <daniel.weise@concepts-and-training.de> - Extended GPL, see LICENSE */

namespace CaT\Doil\Lib\Config;

use CaT\Doil\Lib\Linux\Linux;
use CaT\Doil\Lib\Docker\Docker;
use CaT\Doil\Lib\Logger\LoggerFactory;
use CaT\Doil\Lib\ConsoleOutput\Writer;
use CaT\Doil\Lib\FileSystem\Filesystem;
use Symfony\Component\Console\Output\OutputInterface;

class Keycloak
{
    protected const KEYCLOAK_PATH = "/usr/local/lib/doil/server/keycloak";
    protected const KEYCLOAK_STARTUP_CONF = "/usr/local/lib/doil/server/keycloak/conf/keycloak-startup.conf";
    protected const KEYCLOAK_INIT_SLS = "/usr/local/share/doil/stack/states/keycloak/keycloak/init.sls";
    protected const KEYCLOAK_INIT_SQL = "/usr/local/lib/doil/server/keycloak/conf/init.sql";
    protected const KEYCLOAK_VOLUME_1 = "keycloak_admin";
    protected const KEYCLOAK_VOLUME_2 = "keycloak_keycloak_1";
    protected const SAML_ENABLE_INIT = "/usr/local/share/doil/stack/states/enable-saml/saml/init.sls";
    protected const SAML_DISABLE_INIT = "/usr/local/share/doil/stack/states/disable-saml/saml/init.sls";
    protected const SALT = "/usr/local/lib/doil/server/salt";

    protected Filesystem $filesystem;
    protected Linux $linux;
    protected Writer $writer;
    protected Docker $docker;

    public function __construct(Filesystem $filesystem, Linux $linux, Writer $writer, Docker $docker)
    {
        $this->filesystem = $filesystem;
        $this->linux = $linux;
        $this->writer = $writer;
        $this->docker = $docker;
    }

    public function installKeycloak(OutputInterface $output, array $config, array $persistent): void
    {
        if (!$this->filesystem->exists(self::KEYCLOAK_PATH)) {
            throw new \RuntimeException("Path '" . self::KEYCLOAK_PATH . "' does not exists.");
        }

        $https_scheme = $config['keycloak_https'] ? "https://" : "http://";
        $host = $config['host'];
        $hostname = $https_scheme . $host . "/keycloak";
        $admin_password = $config['keycloak_admin_password'];
        $db_username = $config['keycloak_db_username'];
        $db_password = $config['keycloak_db_password'];

        $persistent_https_scheme = $persistent['keycloak_https'] ? "https://" : "http://";
        $persistent_host = $persistent['host'];
        $persistent_hostname = $persistent_https_scheme . $persistent_host . "/keycloak";
        $persistent_admin_password = $persistent['keycloak_admin_password'];
        $persistent_db_username = $persistent['keycloak_db_username'];
        $persistent_db_password = $persistent['keycloak_db_password'];

        $this->docker->startContainerByDockerCompose(self::SALT, true);
        $this->docker->stopContainerByDockerCompose(self::KEYCLOAK_PATH);
        $this->docker->executeBashCommandInsideContainer(
            "doil_saltmain",
            null,
            "/usr/bin/salt-key -y -q -d doil.keycloak &> /dev/null"
        );

        $this->writer->beginBlock(
            $output,
            "replacing hostname in /usr/local/lib/doil/server/keycloak/conf/keycloak-startup.conf"
        );
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_STARTUP_CONF, "%TPL_SERVER_HOSTNAME%"))) {
            $this->filesystem->replaceStringInFile(self::KEYCLOAK_STARTUP_CONF, "%TPL_SERVER_HOSTNAME%", $hostname);
        }
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_STARTUP_CONF, "--hostname ${persistent_hostname}"))) {
            $this->filesystem->replaceStringInFile(
                self::KEYCLOAK_STARTUP_CONF,
                "--hostname ${persistent_hostname}",
                "--hostname ${hostname}"
            );
        }
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_STARTUP_CONF, "KC_HOSTNAME=\"${persistent_hostname}\""))) {
            $this->filesystem->replaceStringInFile(
                self::KEYCLOAK_STARTUP_CONF,
                "KC_HOSTNAME=\"${persistent_hostname}\"",
                "KC_HOSTNAME=\"${hostname}\""
            );
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing db_username in /usr/local/lib/doil/server/keycloak/conf/init.sql"
        );
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_INIT_SQL, "%TPL_DB_USERNAME%"))) {
            $this->filesystem->replaceStringInFile(self::KEYCLOAK_INIT_SQL, "%TPL_DB_USERNAME%", $db_username);
        }
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_INIT_SQL, "${persistent_db_username}@"))) {
            $this->filesystem->replaceStringInFile(
                self::KEYCLOAK_INIT_SQL,
                "${persistent_db_username}@",
                "${db_username}@"
            );
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing db_password in /usr/local/lib/doil/server/keycloak/conf/init.sql"
        );
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_INIT_SQL, "%TPL_DB_PASSWORD%"))) {
            $this->filesystem->replaceStringInFile(self::KEYCLOAK_INIT_SQL, "%TPL_DB_PASSWORD%", $db_password);
        }
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_INIT_SQL, "BY ${persistent_db_password}"))) {
            $this->filesystem->replaceStringInFile(
                self::KEYCLOAK_INIT_SQL,
                "BY ${persistent_db_password}",
                "BY ${db_username}"
            );
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing new admin password in /usr/local/share/doil/stack/states/keycloak/keycloak/init.sls"
        );
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_INIT_SLS, "%TPL_NEW_ADMIN_PASSWORD%"))) {
            $this->filesystem->replaceStringInFile(self::KEYCLOAK_INIT_SLS, "%TPL_NEW_ADMIN_PASSWORD%", $admin_password);
        }
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_INIT_SLS, "--new-password ${persistent_admin_password}"))) {
            $this->filesystem->replaceStringInFile(
                self::KEYCLOAK_INIT_SLS,
                "--new-password ${persistent_admin_password}",
                "--new-password ${admin_password}"
            );
        }
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_INIT_SLS, "new_admin_password: ${persistent_admin_password}"))) {
            $this->filesystem->replaceStringInFile(
                self::KEYCLOAK_INIT_SLS,
                "new_admin_password: ${persistent_admin_password}",
                "new_admin_password: ${admin_password}"
            );
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing old admin password in /usr/local/share/doil/stack/states/keycloak/keycloak/init.sls"
        );
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_INIT_SLS, "%TPL_OLD_ADMIN_PASSWORD%"))) {
            $this->filesystem->replaceStringInFile(self::KEYCLOAK_INIT_SLS, "%TPL_OLD_ADMIN_PASSWORD%", $persistent_admin_password);
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing db username in /usr/local/lib/doil/server/keycloak/conf/keycloak-startup.conf"
        );
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_STARTUP_CONF, "%TPL_DB_USERNAME%"))) {
            $this->filesystem->replaceStringInFile(self::KEYCLOAK_STARTUP_CONF, "%TPL_DB_USERNAME%", $db_username);
        }
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_STARTUP_CONF, "KC_DB_USERNAME=\"${persistent_db_username}\""))) {
            $this->filesystem->replaceStringInFile(
                self::KEYCLOAK_STARTUP_CONF,
                "KC_DB_USERNAME=\"${persistent_db_username}\"",
                "KC_DB_USERNAME=\"${db_username}\""
            );
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing db password in /usr/local/lib/doil/server/keycloak/conf/keycloak-startup.conf"
        );
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_STARTUP_CONF, "%TPL_DB_PASSWORD%"))) {
            $this->filesystem->replaceStringInFile(self::KEYCLOAK_STARTUP_CONF, "%TPL_DB_PASSWORD%", $db_username);
        }
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_STARTUP_CONF, "KC_DB_PASSWORD=\"${persistent_db_password}\""))) {
            $this->filesystem->replaceStringInFile(
                self::KEYCLOAK_STARTUP_CONF,
                "KC_DB_PASSWORD=\"${persistent_db_password}\"",
                "KC_DB_PASSWORD=\"${db_password}\""
            );
        }
        $this->writer->endBlock();

        $this->writer->beginBlock($output, "rebuilding keycloak container");
        $this->docker->startContainerWithRebuild(self::KEYCLOAK_PATH);
        sleep(120);
        $this->writer->endBlock();

        $this->writer->beginBlock($output, "applying keycloak state");
        $this->docker->applyState("doil.keycloak", "keycloak");
        $this->docker->commit("doil_keycloak", "doil_keycloak");
        $this->writer->endBlock();

        $this->writer->beginBlock($output, "fetching idp metadata");
        $idp_meta_data = trim($this->docker->executeDockerCommandWithReturn(
            "doil_saltmain",
            "salt 'doil.keycloak' http.query http://localhost:8080/realms/master/protocol/saml/descriptor --out=raw | cut -d \"'\" -f6"
        ));
        $this->writer->endBlock();


        $this->writer->beginBlock(
            $output,
            "replacing saml metadata in /usr/local/share/doil/stack/states/enable-saml/saml/init.sls"
        );
        if (!is_null($this->filesystem->getLineInFile(self::SAML_ENABLE_INIT, "idp_meta:"))) {
            $this->filesystem->replaceLineInFile(self::SAML_ENABLE_INIT, "/idp_meta:.*/", "idp_meta: " . $idp_meta_data, -1);
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing keycloak hostname in /usr/local/share/doil/stack/states/enable-saml/saml/init.sls"
        );
        if (!is_null($this->filesystem->getLineInFile(self::SAML_ENABLE_INIT, "keycloak_host_name:"))) {
            $this->filesystem->replaceLineInFile(self::SAML_ENABLE_INIT, "/keycloak_host_name:.*/", "keycloak_host_name: " . $hostname, -1);
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing admin password in /usr/local/share/doil/stack/states/enable-saml/saml/init.sls"
        );
        if (!is_null($this->filesystem->getLineInFile(self::SAML_ENABLE_INIT, "admin_password:"))) {
            $this->filesystem->replaceLineInFile(self::SAML_ENABLE_INIT, "/admin_password:.*/", "admin_password: " . $persistent_admin_password, -1);
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing keycloak hostname in /usr/local/share/doil/stack/states/disable-saml/saml/init.sls"
        );
        if (!is_null($this->filesystem->getLineInFile(self::SAML_DISABLE_INIT, "keycloak_host_name:"))) {
            $this->filesystem->replaceLineInFile(self::SAML_DISABLE_INIT, "/keycloak_host_name:.*/", "keycloak_host_name: " . $hostname, -1);
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing admin password in /usr/local/share/doil/stack/states/disable-saml/saml/init.sls"
        );
        if (!is_null($this->filesystem->getLineInFile(self::SAML_DISABLE_INIT, "admin_password:"))) {
            $this->filesystem->replaceLineInFile(self::SAML_DISABLE_INIT, "/admin_password:.*/", "admin_password: " . $persistent_admin_password, -1);
        }
        $this->writer->endBlock();

        $this->writer->beginBlock(
            $output,
            "replacing admin password in /usr/local/share/doil/stack/states/keycloak/keycloak/init.sls"
        );
        if (!is_null($this->filesystem->getLineInFile(self::KEYCLOAK_INIT_SLS, "--password ${persistent_admin_password}"))) {
            $this->filesystem->replaceStringInFile(
                self::KEYCLOAK_INIT_SLS,
                "--password ${persistent_admin_password}",
                "--password ${admin_password}"
            );
        }
        $this->writer->endBlock();
    }

    public function disableKeycloak(OutputInterface $output): void
    {
        if (!$this->docker->isInstanceUp(self::KEYCLOAK_PATH)) {
            $this->writer->beginBlock($output, "starting keycloak server");
            $this->docker->startContainerByDockerCompose(self::KEYCLOAK_PATH);
            $this->writer->endBlock();
        }

        $image_ids = $this->docker->getImageIdsByName("doil_keycloak");

        $this->writer->beginBlock($output, "removing keycloak container");
        $this->docker->deleteInstances(["doil_keycloak"]);
        $this->writer->endBlock();

        $this->writer->beginBlock($output, "removing keycloak images");
        foreach ($image_ids as $image_id) {
            $this->docker->removeImage($image_id);
        }
        $this->writer->endBlock();

        $this->writer->beginBlock($output, "removing keycloak volumes");
        $this->docker->removeVolume(self::KEYCLOAK_VOLUME_1);
        $this->docker->removeVolume(self::KEYCLOAK_VOLUME_2);
        $this->writer->endBlock();
    }
}