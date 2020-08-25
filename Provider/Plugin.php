<?php
namespace Provider;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        echo "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA activate" . PHP_EOL;
        // $installer = new TemplateInstaller($io, $composer);
        // $composer->getInstallationManager()->addInstaller($installer);
    }
    public function uninstall(Composer $composer, IOInterface $io)
    {
        // TODO: Implement uninstall() method.
    }
    public function deactivate(Composer $composer, IOInterface $io)
    {
        // TODO: Implement deactivate() method.
    }
    public static function getSubscribedEvents()
    {
        return array(
            'post-install-cmd' => 'methodToBeCalled',
            'post-update-cmd' => 'methodToBeCalled'
            // ^ event name ^         ^ method name ^
        );
    }
}
