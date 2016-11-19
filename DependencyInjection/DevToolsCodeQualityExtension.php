<?php

namespace DevTools\CodeQualityBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class DevToolsCodeQualityExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('dev_tools_code_quality.inspect_path', $config['inspect_path']);
        $container->setParameter('dev_tools_code_quality.output_path', $config['output_path']);
        $container->setParameter('dev_tools_code_quality.bin_path', $config['bin_path']);

        $features = array();
        foreach ($config['features'] as $featureName) {
            $features[] = $featureName;
        }
        $container->setParameter('dev_tools_code_quality.features', $features);

        $this->processPhpcsConfig($config, $container);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }

    /**
     * @param array            $config
     * @param ContainerBuilder $container
     */
    private function processPhpcsConfig($config, ContainerBuilder $container)
    {
        if (empty($config['phpcs']['standard'])) {
            $config['phpcs']['standard'] = array('Symfony2');
        }

        $standard = $config['phpcs']['standard'];

        // Replace not-default aliases of Standards with paths.
        foreach ($standard as $k => $name) {
            if ($name === 'Symfony2') {
                $standard[$k] = 'vendor/escapestudios/symfony2-coding-standard/Symfony2';
            }
        }

        $container->setParameter('dev_tools_code_quality.phpcs.standard', $standard);
    }
}
