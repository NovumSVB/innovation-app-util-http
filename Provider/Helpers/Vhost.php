<?php

namespace Provider\Helpers;

class Vhost
{
    private $sDomain, $sServerAdmin, $iPort, $sDocumentRoot, $sLogDirectory, $sEnv;

    function __construct(string $sServerAdmin, string $sDomain, int $iPort, string $sDocumentRoot, string $sLogdir, bool $bUseSSL = false, string $sEnv = null)
    {
        $this->sServerAdmin = $sServerAdmin;
        $this->sDomain = $sDomain;
        $this->iPort = $iPort;
        $this->sDocumentRoot = $sDocumentRoot;
        $this->sLogDirectory = $sLogdir;
        $this->sEnv = $sEnv;
    }

    function getContents()
    {
        $sServerAdmin = '';
        if( $this->sServerAdmin)
        {
            $sServerAdmin = PHP_EOL .'ServerAdmin ' . $this->sServerAdmin;
        }
        $sTld = explode('.', $this->sDomain)[0];
        $sSep = DIRECTORY_SEPARATOR;

        $sExtraParams = '';
        if($this->sEnv === 'dev')
        {
            $sExtraParams = 'SetEnv IS_DEVEL true';
        }

        return <<<VHOST

########################################################################################
# Do not change anything in file below, because it is rewritten whenever composer runs #
########################################################################################
#
# This is the configuration file for the vhost {$this->sDomain}. It contains the 
# configuration directives that may added to your webserver manually or are included 
# in your Docker image automatically.
#
# See https://gitlab.com/NovumGit/innovation-app-util-http for detailed information about
# these config files.
# 

<VirtualHost *:{$this->iPort}>
    ServerName {$this->sDomain}{$sServerAdmin}
    {$sExtraParams}
    DocumentRoot {$this->sDocumentRoot}
    <Directory {$this->sDocumentRoot}>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog {$this->sLogDirectory}{$sSep}{$this->sDomain}.apache.error.log
    CustomLog {$this->sLogDirectory}{$sSep}{$this->sDomain}.apache.access.log combined

</VirtualHost>
VHOST;
    }
}
