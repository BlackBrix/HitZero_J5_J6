<?php
/**
 * HitZero system plugin — Joomla 5/6.
 * Original extension by UWiX. GPL-2.0-or-later.
 */
defined('_JEXEC') or die;

use HitzeroRoot\Plugin\System\Hitzero\Extension\Hitzero;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Database\DatabaseInterface;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, static function (Container $container): PluginInterface {
            $plugin = new Hitzero(
                $container->get(DispatcherInterface::class),
                (array) PluginHelper::getPlugin('system', 'hitzero')
            );
            $plugin->setApplication(Factory::getApplication());
            $plugin->setDatabase($container->get(DatabaseInterface::class));
            return $plugin;
        });
    }
};
