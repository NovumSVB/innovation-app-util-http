<?php

namespace Provider\Helpers;

use Provider\Plugin;


class SiteCreator
{
    private $console;
    private $configuration;
    private $packageName;

    function __construct(string $sPackageName, Console $console)
    {
        $this->packageName = $sPackageName;
        $this->console = $console;
        $this->configuration = new Configuration($sPackageName);
    }

    private function getVhostConfigDir(string $sEnv): string
    {
        $sVhostConfigDir = $this->configuration->getVhostDir() . DIRECTORY_SEPARATOR . $sEnv;

        if(!is_dir($sVhostConfigDir))
        {
            mkdir($sVhostConfigDir, 0777, true);
        }
        return $sVhostConfigDir;
    }

    private function createVhost(string $sEnv, array $aSite): void
    {

        if(!isset($aSite['domain']))
        {
            return;
        }
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $vendorDir = dirname(dirname($reflection->getFileName()));

        /**
         * @todo this may not be the best way of doing this.
         */
        $sSystemRoot = getcwd();

        $sServerAdmin = $aSite['server_admin'] ?? '';
        $sProtocol = $aSite['protocol'];

        if($sEnv === 'dev')
        {
            $sDomain = (explode('.', $aSite['domain'])[0]) . '.innovatieap.nl';
        }


        $iPort = (int) ($aSite['port'] ?? ($aSite['protocol'] == 'https') ? 443 : 80);
        $sDocumentRoot = $this->configuration->getDocumentRoot();
        $sLogdir = $this->configuration->getLogDir();
        $oVhost = new Vhost($sServerAdmin, $sDomain, $iPort, $sDocumentRoot, $sLogdir, $sProtocol == 'https', $sEnv);

        $sDestination = $this->getVhostConfigDir($sEnv) . DIRECTORY_SEPARATOR . $aSite['domain'] . '.conf';
        $this->console->log("Creatating vhost file " . $sDestination);

        file_put_contents($sDestination, $oVhost->getContents());
        $this->console->log("Created $sEnv vHost config: $sDestination", Plugin::$installerName);
    }

    public function createAll()
    {

        $this->console->log("Managing vHost configs for package {$this->packageName}", Plugin::$installerName);
        foreach ($this->configuration->getSiteSettings()['site'] as $sEnvironment => $aSite)
        {
            $this->createVhost($sEnvironment, $aSite);
        }
    }


}

