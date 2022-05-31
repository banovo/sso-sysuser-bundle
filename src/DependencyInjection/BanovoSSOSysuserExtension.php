<?php

namespace Banovo\SSOSysuserBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class BanovoSSOSysuserExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('banovo_sso_sysuser', $config);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../config')
        );

        $loader->load('services.yaml');
    }

}
