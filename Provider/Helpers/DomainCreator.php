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

        $sAdminDocumentRoot = $this->configuration->getSystemDir() . DIRECTORY_SEPARATOR . 'admin_public_html';

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
            '',
            '<VirtualHost *:80>',
            '   ServerAlias admin.*',
            '   VirtualDocumentRoot /var/www/%1/',
            '</VirtualHost>'
        ];
        file_put_contents($sDestination, join(PHP_EOL, $aContents));
    }
}