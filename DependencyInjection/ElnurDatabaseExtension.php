<?php
namespace Elnur\DatabaseBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension,
    Symfony\Component\DependencyInjection\ContainerBuilder,
    Symfony\Component\DependencyInjection\Loader\XmlFileLoader,
    Symfony\Component\Config\FileLocator;

class ElnurDatabaseExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader(
            $container, new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');
    }
}
