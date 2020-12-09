<?php

namespace Provider\Helpers;

class Vhost
{
    private string $sDomain;
    private string $sServerAdmin;
    private int $iPort;
    private string $sDocumentRoot;
    private string $sLogDirectory;
    private string $sEnv;
    private array $aParams;

    function __construct(string $sServerAdmin, string $sDomain, int $iPort, string $sDocumentRoot, string $sLogdir, bool $bUseSSL = false, string $sEnv = 'live', array $aParams = [])
    {
        $this->sServerAdmin = $sServerAdmin;
        $this->sDomain = $sDomain;
        $this->iPort = $iPort;
        $this->sDocumentRoot = $sDocumentRoot;
        $this->sLogDirectory = $sLogdir;
        $this->bUseSsl = $bUseSSL;
        $this->sEnv = $sEnv;
        $this->aParams = $aParams;
    }

    function getContents()
    {
        $sServerAdmin = '';
        if( $this->sServerAdmin)
        {
            $sServerAdmin = PHP_EOL .'ServerAdmin ' . $this->sServerAdmin;
        }

        $aExtraParams = [];

        if($this->bUseSsl){
            $aAddUseSsl[] = "SSLCertificateFile /app/data/CertBot/live/{$this->sDomain}/fullchain.pem";
            $aAddUseSsl[] = "SSLCertificateKeyFile /app/data/CertBot/live/{$this->sDomain}/privkey.pem";
            $aAddUseSsl[] = "Include /app/data/CertBot/options-ssl-apache.conf";
        }

        $sSep = DIRECTORY_SEPARATOR;


        if(isset($this->aParams['ENV_VARS']))
        {
            foreach ($this->aParams['ENV_VARS'] as $sVarName => $sVarValue)
            {
                $aExtraParams[] = "SetEnv $sVarName $sVarValue";
            }

        }
        if($this->sEnv === 'dev')
        {
            $aExtraParams[] = "\tSetEnv IS_DEVEL true";
        }

        $sExtraParams = join(PHP_EOL, $aExtraParams);

        $sTop = '';
        $sBottom = '';

        if($this->bUseSsl)
        {
            $sTop = '<IfModule mod_ssl.c>';
            $sBottom = '</IfModule>';
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
$sTop
<VirtualHost *:{$this->iPort}>
    ServerName {$this->sDomain}{$sServerAdmin}
    DocumentRoot {$this->sDocumentRoot}
    <Directory {$this->sDocumentRoot}>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog {$this->sLogDirectory}{$sSep}{$this->sDomain}.apache.error.log
    CustomLog {$this->sLogDirectory}{$sSep}{$this->sDomain}.apache.access.log combined
    {$sExtraParams}
</VirtualHost>
$sBottom

VHOST;
    }
}
