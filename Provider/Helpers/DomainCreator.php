<?php


namespace Provider\Helpers;


use Core\Json\JsonUtils;
use Core\Utils;
use Hi\Helpers\DirectoryStructure;
use PhpParser\Node\Scalar\MagicConst\Dir;

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
    static function makePath(...$aParts):string
    {
        return join(DIRECTORY_SEPARATOR, $aParts);
    }
    private function getVhostConfigDir(string $sEnv): string
    {
        $sVhostConfigDir = self::makePath($this->configuration->getVhostDir(), $sEnv);

        if(!is_dir($sVhostConfigDir))
        {
            mkdir($sVhostConfigDir, 0777, true);
        }
        return $sVhostConfigDir;
    }
    public function create()
    {
        $oDirectoryStructure = new DirectoryStructure();
        $sJsonFile = file_get_contents("./vendor/{$this->packageName}/composer.json");
        $aComposerContents = json_decode($sJsonFile, true);
        $iSystemId = $aComposerContents['extra']['system_id'];
        $sSysroot = self::makePath(Directory::getSystemRoot(), $this->configuration->getSystemDir());

        $sDomainConfigFile = self::makePath($sSysroot, 'config', $iSystemId, 'config.php');
        $aDomainConfig = require $sDomainConfigFile;

        $sAdminDocumentRoot = self::makePath($sSysroot, 'admin_public_html');
        // docs, svb, justitie
        $sDomainBld = explode('.', $aDomainConfig['DOMAIN'])[0];
        $sTestDomain = str_replace($sDomainBld, $sDomainBld . '.test', $aDomainConfig['DOMAIN']);
        $aVhostConfigs = [
            'dev' =>    ['domain' => 'admin.' . $sDomainBld . '.innovatieapp.nl'],
            'test' =>   ['domain' => 'admin.' . $sTestDomain],
            'prod' =>   ['domain' => 'admin.' . $aDomainConfig['DOMAIN']],
        ];

        foreach ($aVhostConfigs as $sEnv => $aVhostConfig)
        {
            $sDomain = $aVhostConfig['domain'];

            $iPort = $aDomainConfig['PORT'] ?? 80;
            $sServerAdmin = $aDomainConfig['SERVER_ADMIN'] ?? 'anton@nui-boutkam.nl';
            $bUseSSL = (isset($aDomainConfig['PROTOCOL'])) ? $aDomainConfig['PROTOCOL'] === 'https' : false;
            $sLogDir = $this->configuration->getLogDir();

            $aParams = [];
            $aParams['ENV_VARS']['SYSTEM_ID'] = $iSystemId;

            $oVhost = new Vhost($sServerAdmin, $sDomain, $iPort, $sAdminDocumentRoot, $sLogDir, $bUseSSL, $sEnv, $aParams);

            $sDestination = self::makePath($this->getVhostConfigDir($sEnv), $sDomain) . '.conf';
            $this->console->log("Creatating vhost file " . $sDestination);

            file_put_contents($sDestination, $oVhost->getContents());
        }

        $sAbsoluteApacheDir = self::makePath(
            Directory::getSystemRoot(),
            $this->configuration->getAssetsDir(),
            'server',
            'http') . DIRECTORY_SEPARATOR;

        $sLogDirPath = $sLogDir . DIRECTORY_SEPARATOR;
        $sSep = DIRECTORY_SEPARATOR;

        $aContents = [
            "# This configuration file loads the vhost configurations",
            "# It is auto generated but once generated it will not be overwritten",
            "# So you can adjust this file to your needs",
            "",
            "<VirtualHost *:80>",
            "   # This virtual hosts directive overwrites the default document root so opening a browser and navigating to",
            "   # 127.0.0.1 shows something meaningfull. You cannot make modifications here as this will be overwritten on",
            "   # everytime you run composer update or composer install.",
            "   ServerAdmin webmaster@localhost",
            "   DocumentRoot /app/.system/public_html/docs.demo.novum.nu/public_html",
            "   SetEnv IS_DEVEL true",
            "",
            "   <Directory /app/.system/public_html/docs.demo.novum.nu>",
            "       AllowOverride All",
            "       Require all granted",
            "    </Directory>",
            "",
            "   ErrorLog {$sLogDirPath}default-site.error.log",
            "   CustomLog {$sLogDirPath}default-site.access.log combined",
            "",
            "</VirtualHost>",
            "",
            "# Include the prod vhost host configurations:",
            "IncludeOptional {$sAbsoluteApacheDir}prod{$sSep}*.conf",
            "",
            "# Include the dev vhost configurations:",
            "IncludeOptional {$sAbsoluteApacheDir}dev{$sSep}*.conf",
            "",
            "# Include the test vhost configurations:",
            "IncludeOptional {$sAbsoluteApacheDir}test{$sSep}*.conf",
            "",
        ];

        $sServerConfigFilename = self::makePath($this->configuration->getVhostDir(), 'server.conf');

        file_put_contents($sServerConfigFilename, join(PHP_EOL, $aContents));
    }
}
