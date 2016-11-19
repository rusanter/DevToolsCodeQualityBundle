<?php

namespace DevTools\CodeQualityBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $treeBuilder->root('dev_tools_code_quality')
            ->children()
                ->scalarNode('inspect_path')->defaultValue('src')->end()
                ->scalarNode('output_path')->defaultValue('web/qa')->end()
                ->scalarNode('bin_path')->defaultValue('bin')->end()
                ->arrayNode('features')
                    ->treatNullLike(array())
                    ->prototype('scalar')->end()
                    ->defaultValue(array(
                        'phploc',
                        'pdepend',
                        'phpmd',
                        'phpcpd',
                        'phpcs',
                    ))
                    ->end()
                ->arrayNode('phpcs')
                    ->treatNullLike(array())
                    ->children()
                        ->arrayNode('standard')
                            ->treatNullLike(array('Symfony2'))
                            ->prototype('scalar')->end()
                            ->defaultValue(array('Symfony2'))
                            ->end()
            ;

        return $treeBuilder;
    }
}
