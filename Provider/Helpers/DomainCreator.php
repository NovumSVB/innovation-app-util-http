<?php


namespace Provider\Helpers;


class DomainCreator
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

    public function create()
    {
        $this->console->log("Creating main server configuration");
        $sDestination = $this->configuration->getVhostDir() . '/server.conf';
        $this->console->log($sDestination);

        $sAdminDocumentRoot = Directory::getSystemRoot() .
                                DIRECTORY_SEPARATOR .
                                $this->configuration->getSystemDir() .
                                DIRECTORY_SEPARATOR .
                                'admin_public_html';

        $sLogRoot = Directory::getSystemRoot() .
                                DIRECTORY_SEPARATOR .
                                $this->configuration->getLogDir();

        $sAdminLogDir = $sLogRoot . DIRECTORY_SEPARATOR;
        $sAdminLogFile = $sLogRoot . DIRECTORY_SEPARATOR;
        $sSep = DIRECTORY_SEPARATOR;

        $aContents = [
            '# This configuration file loads the vhost configurations',
            '# It is auto generated but once generated it will not be overwritten',
            '# So you can adjust this file to your needs',
            '',
            '# Include the prod vhost host configurations:',
            "IncludeOptional prod{$sSep}*.conf",
            '',
            '# Include the dev vhost configurations:',
            "# IncludeOptional dev{$sSep}*.conf",
            '',
            '# Include the test vhost configurations:',
            "# IncludeOptional test{$sSep}*.conf",
            '',
            '<VirtualHost *:80>',
            '   ServerAlias admin.*.innovatieapp.nl',
            '   ServerAlias admin.innovatieapp.nl',
            '   ServerAlias admin.demo.nuicart.nl',
            "   VirtualDocumentRoot {$sAdminDocumentRoot}",
            "   <Directory {$sAdminDocumentRoot}>",
            'AllowOverride All',
            'Require all granted',
            '</Directory>',
            "ErrorLog {$sLogRoot}admin-panels.apache.error.log",
            "CustomLog {$sLogRoot}admin-panels.apache.access.log combined",
            '</VirtualHost>'
        ];
        file_put_contents($sDestination, join(PHP_EOL, $aContents));
    }
}