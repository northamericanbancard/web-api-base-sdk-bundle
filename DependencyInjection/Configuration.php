<?php

/**
 * Filename:    Configuration.php
 * Created:     13/10/17, at 3:07 PM
 * @author      James Hollenbeck <jhollenbeck@nabancard.com>
 * @copyright   1992-2017 North American Bancard
 */

namespace NAB\Bundle\WebApiBaseSdk\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class that sets up the what this bundle expects in terms of
 * config*.yml files in main projects.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->getConfigurationRootKey());

        $rootNode
            ->children()
                ->arrayNode('guzzle_configuration')
                    ->children()
                        ->scalarNode('http_errors')->defaultFalse()->end()
                        ->scalarNode('decode_content')->defaultTrue()->end()
                        ->scalarNode('verify')->defaultTrue()->end()
                        ->scalarNode('cookies')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('endpoints')
                ->useAttributeAsKey('name')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('base_endpoint')->isRequired()->end()
                        ->scalarNode('api_key')->defaultNull()->end()
                        ->arrayNode('jwt')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('token')->isRequired()->end()
                            ->end()
                        ->end()
                        ->arrayNode('aws')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('aws_region')->defaultValue('us-east-1')->end()
                                ->scalarNode('aws_service')->defaultValue('execute-api')->end()
                                ->arrayNode('credentials')
                                    ->isRequired()
                                    ->children()
                                        ->scalarNode('access_key')->isRequired()->end()
                                        ->scalarNode('secret_key')->isRequired()->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    /**
     * This method is used to get the root key for the configuration. If you are creating a specialized bundle,
     * inherit from this class, and override this function to return (new MyConfiguration())->getAlias().
     *
     * {@see NABWebApiBaseSdkExtension::getAlias}
     *
     * @return string
     */
    protected function getConfigurationRootKey()
    {
        return (new NABWebApiBaseSdkExtension())->getAlias();
    }
}
