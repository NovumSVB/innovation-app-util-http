<?php

namespace Provider\Helpers;

use Provider\Plugin;


class Creator
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


        $sServerAdmin = $aSite['server_admin'] ?? '';
        $sProtocol = $aSite['protocol'];
        $sDomain = $aSite['domain'] ?? 'https';
        $iPort = (int) ($aSite['port'] ?? ($aSite['protocol'] == 'https') ? 443 : 80);
        $sDocumentRoot = $this->configuration->getDocumentRoot();
        $sLogdir = $this->configuration->getLogDir();
        $oVhost = new Vhost($sServerAdmin, $sDomain, $iPort, $sDocumentRoot, $sLogdir, $sProtocol == 'https');

        $sDestination = $this->getVhostConfigDir($sEnv) . DIRECTORY_SEPARATOR . $aSite['domain'] . '.conf';

        file_put_contents($sDestination, $oVhost->getContents());
        $this->console->log("Created $sEnv vHost config: $sDestination", Plugin::$installerName);
    }

    public function createMain()
    {
        $sDestination = $this->configuration->getVhostDir() . 'server.conf';
        $aContents = [
            '# This configuration file loads the vhost configurations',
            '# It is auto generated but once generated it will not be overwritten',
            '# So you can adjust this file to your needs',
            '',
            '# Include the dev vhost configurations:',
            '# IncludeOptional dev/*.conf',
            '',
            '# Include the prod vhost host configurations:',
            'IncludeOptional prod/*.conf',
            '',
            '# Include the test vhost configurations:',
            '# IncludeOptional test/*.conf',
        ];
        file_put_contents($sDestination, join(PHP_EOL, $aContents));
    }

    public function createAll()
    {
        $this->createMain();
        $this->console->log("Managing vHost configs for package {$this->packageName}", Plugin::$installerName);
        foreach ($this->configuration->getSiteSettings()['site'] as $sEnvironment => $aSite)
        {
            $this->createVhost($sEnvironment, $aSite);
        }
    }


}
