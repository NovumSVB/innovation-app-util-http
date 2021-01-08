<?php


namespace Provider\Helpers;


use Hi\Helpers\DirectoryStructure;

class DomainCreator
{
    private Console $console;
    private Configuration $configuration;
    private string $packageName;

    function __construct(string $sPackageName, Console $console)
    {
        $this->packageName = $sPackageName;
        $this->console = $console;
        $this->configuration = new Configuration($sPackageName);
    }

    public function create()
    {
        $this->createDomainVhostConfig();
        $this->createMainApacheConfig();

    }

    /**
     * @return void
     */
    private function createDomainVhostConfig(): void
    {
        $oDirectoryStructure = new DirectoryStructure();
        $sJsonFile = file_get_contents("./vendor/{$this->packageName}/composer.json");
        $aComposerContents = json_decode($sJsonFile, true);
        $iSystemId = $aComposerContents['extra']['system_id'];
        $sSysroot = self::makePath(Directory::getSystemRoot(), $this->configuration->getSystemDir());

        $sDomainConfigFile = self::makePath($oDirectoryStructure->getSystemRoot(), 'vendor', $this->packageName, 'config.php');
        $aDomainConfig = require $sDomainConfigFile;

        $sAdminDocumentRoot = self::makePath($sSysroot, 'admin_public_html');


        $aVhostConfigs = $this->getEnvironmentOptions($aDomainConfig['DOMAIN']);

        foreach ($aVhostConfigs as $sEnv => $aVhostConfig) {
            $sDomain = $aVhostConfig['domain'];


            if ($sEnv === 'dev') {
                $iPort = 80;
                $bUseSSL = false;
            } else {
                if (isset($aDomainConfig['PORT'])) {
                    $iPort = $aDomainConfig['PORT'];
                } else if (isset($aDomainConfig['PROTOCOL'])) {
                    $iPort = $aDomainConfig['PROTOCOL'] === 'https' ? 443 : 80;
                } else {
                    $iPort = 80;
                }
                $bUseSSL = (isset($aDomainConfig['PROTOCOL'])) ? $aDomainConfig['PROTOCOL'] === 'https' : false;
            }
            $sServerAdmin = $aDomainConfig['SERVER_ADMIN'] ?? 'anton@nui-boutkam.nl';
            $sLogDir = $this->configuration->getLogDir();

            $aParams = [];
            $aParams['ENV_VARS']['SYSTEM_ID'] = $iSystemId;

            $oVhost = new Vhost($sServerAdmin, $sDomain, $iPort, $sAdminDocumentRoot, $sLogDir, $bUseSSL, $sEnv, $aParams);
            $sDestination = self::makePath($this->getVhostConfigDir($sEnv), $sDomain) . '.conf';
            $this->console->log("Creatating vhost file " . $sDestination);

            file_put_contents($sDestination, $oVhost->getContents());
        }
    }

    /**
     * @param $sDomain
     * @return string[]
     */
    private function getEnvironmentOptions($sDomain): array
    {
// docs, svb, justitie
        $sDomainBld = explode('.', $sDomain)[0];
        $sTestDomain = str_replace($sDomainBld, $sDomainBld . '.test', $sDomain);
        return [
            'dev' => ['domain' => 'admin.' . $sDomainBld . '.innovatieapp.nl'],
            'test' => ['domain' => 'admin.' . $sTestDomain],
            'prod' => ['domain' => 'admin.' . $sDomain],
        ];
    }

    private function getVhostConfigDir(string $sEnv): string
    {
        $sVhostConfigDir = self::makePath($this->configuration->getVhostDir(), $sEnv);

        if (!is_dir($sVhostConfigDir)) {
            mkdir($sVhostConfigDir, 0777, true);
        }
        return $sVhostConfigDir;
    }

    static function makePath(...$aParts): string
    {
        return join(DIRECTORY_SEPARATOR, $aParts);
    }

    /**
     */
    private function createMainApacheConfig(): void
    {
        $aContents = $this->makeMainApacheConfig();

        $sServerConfigFilename = self::makePath($this->configuration->getVhostDir(), 'server.conf');

        if (!file_exists($sServerConfigFilename)) {
            file_put_contents($sServerConfigFilename, join(PHP_EOL, $aContents));
        } else {
            $this->console->log("Skip creation of <info>$sServerConfigFilename</info>, file exists");
        }
    }

    /**
     * @return string[]
     */
    private function makeMainApacheConfig(): array
    {
        $sLogDir = $this->configuration->getLogDir();

        $sAbsoluteApacheDir = self::makePath(
                Directory::getSystemRoot(),
                $this->configuration->getAssetsDir(),
                'server',
                'http') . DIRECTORY_SEPARATOR;

        $sLogDirPath = $sLogDir . DIRECTORY_SEPARATOR;
        $sSep = DIRECTORY_SEPARATOR;

        $sDisableLiveEnvironments = '';
        $sDisableDevEnvironments = '#';
        if (isset($_SERVER['IS_DEVEL']) || isset($_ENV['IS_DEVEL'])) {
            $sDisableLiveEnvironments = '#';
            $sDisableDevEnvironments = '';
        }

        return [
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
            "{$sDisableLiveEnvironments}IncludeOptional {$sAbsoluteApacheDir}prod{$sSep}*.conf",
            "",
            "# Include the test vhost configurations:",
            "{$sDisableLiveEnvironments}IncludeOptional {$sAbsoluteApacheDir}test{$sSep}*.conf",
            "",
            "# Include the dev vhost configurations:",
            "{$sDisableDevEnvironments}IncludeOptional {$sAbsoluteApacheDir}dev{$sSep}*.conf",
            "",
            "",
        ];
    }
}
