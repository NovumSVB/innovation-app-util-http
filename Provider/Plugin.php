<?php

namespace Provider;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Provider\Helpers\Cleaner;
use Provider\Helpers\Configuration;
use Provider\Helpers\Console;
use Provider\Helpers\Creator;

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

    /**
     * @param PackageEvent $event
     * @throws \Exception
     */

    public function postInstall(Event $event)
    {
        $sPackageName = $event->getComposer()->getPackage()->getName();
        $console = new Console($event->getIO());
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

                if(!preg_match('/(novum|hurah)-(site|api)/', $oPackageConfig->getComposerJson()['type']))
                {
                    continue;
                }
                $bHasCandidates = true;
                $oCreator = new Creator($sPackageName, $console);
                $oCreator->createAll();
            }
        }

        if(!$bHasCandidates)
        {
            $console->log("Did not create any Vhosts, this may be because no API\'s/Websites have been added yet.");
        }
    }
    public function postUpdate(Event $event)
    {
        $console = new Console($event->getIO());
        $console->log("Running post package update " . $event->getComposer()->getPackage()->getName(), self::$installerName);

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
