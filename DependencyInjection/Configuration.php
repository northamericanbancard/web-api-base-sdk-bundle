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
                    ->info('Set the configuration as expected by a GuzzleHttp\Client')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('http_errors')->defaultFalse()->end()
                        ->scalarNode('decode_content')->defaultTrue()->end()
                        ->scalarNode('verify')->defaultTrue()->end()
                        ->scalarNode('cookies')->defaultFalse()->end()
                    ->end()
                ->end()
                ->arrayNode('endpoints')
                ->info('The collection of base-urls and SDK configuration')
                ->useAttributeAsKey('name')
                ->requiresAtLeastOneElement()
                ->prototype('array')
                    ->children()
                        ->scalarNode('wrapped_by')
                            ->info(
                                'If this bundle is being used to turn a library SDK into a service, map each endpoint' .
                                ' to the proper library SDK class. This SDK lib should be based off of the' .
                                ' web-api-base-sdk lib.'
                            )
                            ->defaultNull()
                            ->end()
                        ->scalarNode('base_endpoint')->info('The base URL, no trailing /')->isRequired()->end()
                        ->scalarNode('api_key')
                            ->info('The x-api-header value (usually for AWS APIG)')
                            ->defaultNull()
                        ->end()
                        ->arrayNode('jwt')
                            ->info('Once enabled, use JWT as your authentication service: "Authorization: Bearer".')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('token')->isRequired()->end()
                            ->end()
                        ->end()
                        ->arrayNode('basic')
                            ->info('Once enabled, use Basic as your authentication service: "Authorization: Basic".')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('username')->isRequired()->end()
                                ->scalarNode('password')->isRequired()->end()
                            ->end()
                        ->end()
                        ->arrayNode('aws')
                            ->info('Once enabled, use IAM Access/Secrete keys for authentication.')
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
