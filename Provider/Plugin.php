<?php

namespace Provider;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Hi\Installer\Util;
use Provider\Helpers\Cleaner;
use Provider\Helpers\Configuration;
use Provider\Helpers\Console;
use Provider\Helpers\DomainCreator;
use Provider\Helpers\SiteCreator;

class Plugin implements PluginInterface, EventSubscriberInterface
{
    private $composer;
    public static $installerName = 'Novum http util';

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $console = new Console($io);

        $console->log("Initializing http util");
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {

    }
    public function uninstall(Composer $composer, IOInterface $io)
    {

    }

    /**
     * @param PackageEvent $event
     * @throws \Exception
     */

    public function postInstall(Event $event)
    {
        $sPackageName = $event->getComposer()->getPackage()->getName();
        $console = new Console($event->getIO());

        if($sPackageName !== 'novum/innovation-app-util-docker') {
            $console->log("Not running post package update <comment>" . $sPackageName . " !== novum/innovation-app-util-docker</comment>", self::$installerName);
            return;
        }

        $console->log("Generating vHost configurations " . $sPackageName, self::$installerName);

        $aRequiredPackages = $event->getComposer()->getPackage()->getRequires();

        $oPackageConfig = new Configuration($sPackageName);

        Cleaner::removePrevious($oPackageConfig, $console);
        $bHasCandidates = false;
        if(is_array($aRequiredPackages))
        {
            foreach ($aRequiredPackages as $sPackageName => $oPackageProperties)
            {
                $oPackageConfig = new Configuration($sPackageName);

                if(!$oPackageConfig->getComposerJson())
                {
                    continue;
                }
                if(preg_match('/(novum|hurah)-(domain)/', $oPackageConfig->getComposerJson()['type']))
                {
                    $console->log("Creating domain package <info>$sPackageName</info>");
                    $oMainCreator = new DomainCreator($sPackageName, $console);
                    $oMainCreator->create();
                }
                if(preg_match('/(novum|hurah)-(site|api)/', $oPackageConfig->getComposerJson()['type']))
                {
                    if($oPackageConfig->getComposerJson()['extra'])
                    {
                        /***
                         * When the main composer.json only contains a site / an api the domain is also installed via
                         * a dependency it won't be available so we are looking it up and installing it anyway.
                         */
                        $sConfigDir = $oPackageConfig->getSiteSettings()['config_dir'];
                        $sDomainComposerPath = Util::makePath('domain', $sConfigDir, 'composer.json');

                        if (file_exists($sDomainComposerPath))
                        {
                            $sComposerFile = file_get_contents($sDomainComposerPath);
                            $aDomainComposerFile = json_decode($sComposerFile, true);
                            $sShortComposerName = $aDomainComposerFile['name'];
                            $console->log("Creating domain package <info>$sShortComposerName</info>, <comment>via dependency</comment>");
                            $oMainCreator = new DomainCreator($sShortComposerName, $console);
                            $oMainCreator->create();
                        }
                    }
                    $bHasCandidates = true;
                    $oCreator = new SiteCreator($sPackageName, $console);
                    $oCreator->createAll();
                }
            }
        }

        if(!$bHasCandidates)
        {
            $console->log("Did not create any Vhosts, this may be because no API\'s/Websites have been added yet.");
        }
        else
        {
            $console->log("<warning>Webserver configuration files have been (re)written, the webserver needs a restart for these changes to take effect.</warning>");
        }

    }
    public function postUpdate(Event $event)
    {
        $console = new Console($event->getIO());

        if($event->getComposer()->getPackage()->getName() !== 'novum/innovation-app-util-docker') {
            $console->log("Not running post package update <comment>" . $event->getComposer()->getPackage()->getName() . " !== novum/innovation-app-util-docker</comment>", self::$installerName);
            return;
        }
        $console->log("Running post package update <comment>" . $event->getComposer()->getPackage()->getName() . "</comment>", self::$installerName);

        $this->postInstall($event);
    }
    public static function getSubscribedEvents()
    {
        return [
            'post-install-cmd' => 'postInstall',
            'post-update-cmd'  => 'postUpdate',
        ];
    }
}
