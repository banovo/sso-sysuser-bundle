<?php

namespace Banovo\SSOSysuserBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{

    public function getConfigTreeBuilder()
    {

        $treeBuilder = new TreeBuilder('banovo_sso_sysuser');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->ignoreExtraKeys();

        $rootNode
            ->children()
                ->arrayNode('sso_configuration')
                    ->prototype('array')
                        ->prototype('scalar')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

}
