<?php

/**
 * Filename:    NABWebApiBaseSdkExtension.php
 * Created:     13/10/17, at 3:05 PM
 * @author      James Hollenbeck <jhollenbeck@nabancard.com>
 * @copyright   1992-2017 North American Bancard
 */

namespace NAB\Bundle\WebApiBaseSdk\DependencyInjection;

use Aws\Credentials\Credentials;
use NAB\WebApiBaseSdk as Client;
use NAB\WebApiBaseSdk\SignatureV4;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * Bundle configuration processor and service generator for our SDK client.
 */
class NABWebApiBaseSdkExtension extends Extension
{
    /**
     * @var string
     */
    const CONFIGURATION_ALIAS = 'nab_web_api_base_sdk';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $this->configureClients($container, $config);
    }

    /**
     * Returns the recommended alias to use in XML.
     *
     * This alias is also the mandatory prefix to use when using YAML.
     *
     * This convention is to remove the "Extension" postfix from the class
     * name and then lowercase and underscore the result. So:
     *
     *     AcmeHelloExtension
     *
     * becomes
     *
     *     acme_hello
     *
     * NOTE: If you are creating a specialized SDK, simply have your extension inherit this class, and override
     * the CONFIGURATION_ALIAS constant. {@see NABWebApiBaseSdkExtension::getConfigurationRootKey}
     *
     * @return string The alias
     *
     * @throws BadMethodCallException When the extension name does not follow conventions
     */
    public function getAlias()
    {
        return static::CONFIGURATION_ALIAS;
    }

    /**
     * Helper method to configure the SDK client as a service. We are using this here rather than
     * creating parameters and using the services.yml file to keep the container free of excess params.
     *
     * @param ContainerBuilder $container Symfony's container builder
     * @param array $config               The config that's been processed between our Configuration
     *                                    and the project that is including this bundle
     */
    private function configureClients(ContainerBuilder $container, array $config)
    {
        $serviceKeyTemplate = 'nab.web_api_sdk.%s.%s_client';
        $guzzleConfiguration = $config['guzzle_configuration'];
        $serviceDefiner = null;

        foreach ($config['endpoints'] as $endpointServiceDefinitionKey => $endpoint) {
            $baseEndpoint = $endpoint['base_endpoint'];
            if (isset($endpoint['aws']) && $endpoint['aws']['enabled']) {
                $serviceKey = sprintf($serviceKeyTemplate, $endpointServiceDefinitionKey, 'aws');
                $serviceDefiner = 'setAwsService';
            } elseif (isset($endpoint['jwt']) && $endpoint['jwt']['enabled']) {
                $serviceKey = sprintf($serviceKeyTemplate, $endpointServiceDefinitionKey, 'jwt');
                $serviceDefiner = 'setJwtService';
            } else {
                $serviceKey = sprintf($serviceKeyTemplate, $endpointServiceDefinitionKey, 'simple');
                $serviceDefiner = 'setSimpleService';
            }

            if ($serviceDefiner) {
                $this->$serviceDefiner(
                    $container,
                    $serviceKey,
                    $baseEndpoint,
                    $endpoint,
                    $guzzleConfiguration
                );
            }
        }
    }

    /**
     * Sets up any requested AWS-capable callers.
     *
     * @param ContainerBuilder $containerBuilder An instance of Symfony's Container Builder
     * @param string $serviceKey                 The service key to access this service using container->get
     * @param string $baseEndpoint               The base url where all SDK calls stem from
     * @param array $endpointConfig              The complete configuration for the current endpoint
     * @param array $guzzleConfiguration         The guzzle config to pass up to the Guzzle client
     */
    private function setAwsService(
        ContainerBuilder $containerBuilder,
        $serviceKey,
        $baseEndpoint,
        array $endpointConfig,
        array $guzzleConfiguration
    ) {
        $awsConfig = $endpointConfig['aws'];
        $sigV4 = new Definition(Client\SignatureV4::class, [$awsConfig['aws_service'], $awsConfig['aws_region']]);

        $credentialsConfig = $awsConfig['credentials'];
        $credentials = new Definition(
            Credentials::class,
            [
                $credentialsConfig['access_key'],
                $credentialsConfig['secret_key'],
            ]
        );

        $clientDefinition = new Definition(
            Client\AwsApiGatewayClient::class,
            [
                $baseEndpoint,
                $sigV4,
                $credentials,
                $endpointConfig['api_key'],
                $guzzleConfiguration
            ]
        );

        $containerBuilder->setDefinition($serviceKey, $clientDefinition);
    }

    /**
     * Sets up any requested JWT-capable callers.
     *
     * @param ContainerBuilder $containerBuilder An instance of Symfony's Container Builder
     * @param string $serviceKey                 The service key to access this service using container->get
     * @param string $baseEndpoint               The base url where all SDK calls stem from
     * @param array $endpointConfig              The complete configuration for the current endpoint
     * @param array $guzzleConfiguration         The guzzle config to pass up to the Guzzle client
     */
    private function setJwtService(
        ContainerBuilder $containerBuilder,
        $serviceKey,
        $baseEndpoint,
        array $endpointConfig,
        array $guzzleConfiguration
    ) {
        $jwtConfig = $endpointConfig['jwt'];
        $token = $jwtConfig['token'];

        $clientDefinition = new Definition(
            Client\JwtClient::class,
            [
                $baseEndpoint,
                $token,
                $endpointConfig['api_key'],
                $guzzleConfiguration
            ]
        );

        $containerBuilder->setDefinition($serviceKey, $clientDefinition);
    }

    /**
     * Sets up any requested non-authenticated API SDKs.
     *
     * @param ContainerBuilder $containerBuilder An instance of Symfony's Container Builder
     * @param string $serviceKey                 The service key to access this service using container->get
     * @param string $baseEndpoint               The base url where all SDK calls stem from
     * @param array $endpointConfig              The complete configuration for the current endpoint
     * @param array $guzzleConfiguration         The guzzle config to pass up to the Guzzle client
     */
    private function setSimpleService(
        ContainerBuilder $containerBuilder,
        $serviceKey,
        $baseEndpoint,
        array $endpointConfig,
        array $guzzleConfiguration
    ) {
        $clientDefinition = new Definition(
            Client\SimpleClient::class,
            [
                $baseEndpoint,
                $endpointConfig['api_key'],
                $guzzleConfiguration
            ]
        );

        $containerBuilder->setDefinition($serviceKey, $clientDefinition);
    }
}
