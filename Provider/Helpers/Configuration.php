<?php
namespace Provider\Helpers;

class Configuration
{

    private $packageName;
    private $sPackageRoot;
    private $sPackageJsonFile;
    private $aComposerjson;
    private $aSiteSettings = [];
    private $vhostDir;

    /**
     * Configuration constructor.
     * @param $sPackageName
     */
    function __construct($sPackageName)
    {
        $this->packageName = $sPackageName;

        $this->vhostDir = $this->getAssetsDir() . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . 'http';

        $projectRootPath = dirname(\Composer\Factory::getComposerFile());

        $this->sPackageRoot = $projectRootPath . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . $sPackageName;
        $sSiteSettingsFile = $this->sPackageRoot . DIRECTORY_SEPARATOR  . 'site.json';

        if(file_exists($sSiteSettingsFile))
        {
            $this->aSiteSettings = json_decode(file_get_contents($sSiteSettingsFile), true);
        }


        $this->sPackageJsonFile = $this->sPackageRoot . DIRECTORY_SEPARATOR . 'composer.json';

        if (!file_exists($this->sPackageJsonFile))
        {
            return null;
        }
        $sPackageJson = file_get_contents($this->sPackageJsonFile);
        $this->aComposerjson = json_decode($sPackageJson, true);
    }


    function getVhostDir():string
    {
        if(!is_dir($this->vhostDir))
        {
            mkdir($this->vhostDir, 0777, true);
        }
        return $this->vhostDir;
    }

    public function getSiteSettings():array
    {
        return $this->aSiteSettings;
    }

    public function getEnvironments():array
    {
        return ['prod', 'test', 'dev'];
    }

    public function getComposerJson()
    {
        return $this->aComposerjson;
    }
    /**
     * @return array
     */
    private function getDirectoriesJson():array
    {
        $sDirectoriesJsonFile = './vendor/hurah/hurah-installer/directory-structure.json';
        $sDirectoriesJsonContent = file_get_contents($sDirectoriesJsonFile);
        $aDirectoriesJson = json_decode($sDirectoriesJsonContent, true);

        return $aDirectoriesJson;
    }
    public function getSystemDir():string
    {
        return self::getDirectoriesJson()['system_dir'];
    }
    public function getPublicDir():string
    {
        return self::getDirectoriesJson()['public_dir'];
    }
    public function getDocumentRoot(): string
    {
        $sInstallationDirectory = $this->getComposerJson()['extra']['install_dir'];
        return Directory::getSystemRoot() . DIRECTORY_SEPARATOR . $this->getPublicDir() . DIRECTORY_SEPARATOR . $sInstallationDirectory;
    }
    public function getLogDir():string
    {
        return Directory::getSystemRoot() . DIRECTORY_SEPARATOR . self::getDirectoriesJson()['log_dir'];
    }
    public function getAssetsDir():string
    {
        return self::getDirectoriesJson()['assets_dir'];
    }

}
