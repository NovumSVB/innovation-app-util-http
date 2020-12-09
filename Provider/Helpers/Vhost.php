<?php

namespace Provider\Helpers;

class Vhost
{
    private $sDomain, $sServerAdmin, $iPort, $sDocumentRoot, $sLogDirectory;
    private bool $bUseSsl;

    function __construct(string $sServerAdmin, string $sDomain, int $iPort, string $sDocumentRoot, string $sLogdir, bool $bUseSSL = false)
    {
        $this->sServerAdmin = $sServerAdmin;
        $this->sDomain = $sDomain;
        $this->iPort = $iPort;
        $this->sDocumentRoot = $sDocumentRoot;
        $this->sLogDirectory = $sLogdir;
        $this->bUseSsl = $bUseSSL;
    }

    function getContents()
    {
        $sServerAdmin = '';
        if( $this->sServerAdmin)
        {
            $sServerAdmin = PHP_EOL .'ServerAdmin ' . $this->sServerAdmin;
        }

        $aAddUseSsl = [];
        if($this->bUseSsl){
            $aAddUseSsl[] = "SSLCertificateFile /app/data/CertBot/live/{$this->sDomain}/fullchain.pem";
            $aAddUseSsl[] = "SSLCertificateKeyFile /app/data/CertBot/live/{$this->sDomain}/privkey.pem";
            // $aAddUseSsl[] = "Include /etc/letsencrypt/options-ssl-apache.conf";
        }

        $sUseSSl = join(PHP_EOL, $aAddUseSsl);

        $sSep = DIRECTORY_SEPARATOR;
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
{($this->bUseSsl ? '<IfModule mod_ssl.c>' : '')}
<VirtualHost *:{$this->iPort}>
ServerName {$this->sDomain}{$sServerAdmin}
DocumentRoot {$this->sDocumentRoot}
<Directory {$this->sDocumentRoot}>
AllowOverride All
Require all granted
</Directory>

ErrorLog {$this->sLogDirectory}{$sSep}{$this->sDomain}.error.log
CustomLog {$this->sLogDirectory}{$sSep}{$this->sDomain}.access.log combined

</IfModule>
{($this->bUseSsl ? '<IfModule mod_ssl.c>' : '')}

VHOST;
    }
}
