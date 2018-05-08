<?php

/**
 * Filename:    NABWebApiBaseSdkExtensionTest.php
 * Created:     16/10/17, at 2:26 PM
 * @author      James Hollenbeck <jhollenbeck@nabancard.com>
 * @copyright   1992-2017 North American Bancard
 */

namespace NAB\Bundle\WebApiBaseSdk\Tests\DependencyInjection;

use Aws\Credentials\Credentials;
use NAB\Bundle\WebApiBaseSdk\DependencyInjection\Configuration;
use NAB\WebApiBaseSdk\AwsApiGatewayClient;
use NAB\Bundle\WebApiBaseSdk\DependencyInjection\NABWebApiBaseSdkExtension;
use NAB\WebApiBaseSdk\BasicClient;
use NAB\WebApiBaseSdk\JwtClient;
use NAB\WebApiBaseSdk\SignatureV4;
use NAB\WebApiBaseSdk\SimpleClient;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Test the various ways we get services created, and that they 'seem' correct in terms of constructor
 * arguments and service definition class names.
 */
class NABWebApiBaseSdkExtensionTest extends KernelTestCase
{
    /**
     * @var NABWebApiBaseSdkExtension
     */
    private $systemUnderTest;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->systemUnderTest = new NABWebApiBaseSdkExtension();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->systemUnderTest);
    }

    public function extensionWillSetExpectedServiceDataProvider()
    {
        $testDataBase = [
            'nab_web_api_base_sdk' => [
                'guzzle_configuration' => [],
                'endpoints' => [
                    'test' => [
                        'base_endpoint' => 'test.com',
                        'api_key' => 'abc',
                    ],
                    'derp' => [
                        'base_endpoint' => 'derp.com',
                        'api_key' => 'abc',
                    ]
                ]
            ]
        ];

        $credentials = [
            'jwt' => ['jwt' => ['token' => 'my.tok.en']],
            'aws' => ['aws' => ['credentials' => ['access_key' => 1, 'secret_key' => 2,]]],
            'basic' => ['basic' => ['username' => 'user', 'password' => 'password',]],
            'simple' => [],
        ];

        $guzzleConfigurationBase = [
            'http_errors' => false,
            'decode_content' => true,
            'verify' => true,
            'cookies' => false,
            'allow_redirects' => true,
            'cert' => null,
            'connect_timeout' => 5.0,
            'debug' => false,
            'headers' => [],
            'ssl_key' => null,
            'stream' => false,
            'timeout' => 5.0,
        ];

        $jwtConfiguration = $testDataBase;
        $awsConfiguration = $testDataBase;
        $simpleConfiguration = $testDataBase;
        $basicConfiguration = $testDataBase;

        foreach (['test', 'derp'] as $clientKey) {
            foreach ([
                'jwt' => &$jwtConfiguration,
                'aws' => &$awsConfiguration,
                'simple' => &$simpleConfiguration,
                'basic' => &$basicConfiguration,
            ] as $key => &$testData) {
                    $testData['nab_web_api_base_sdk']['endpoints'][$clientKey] += $credentials[$key];
            }
        }

        return [
            'simple' => [
                'configuration' => $simpleConfiguration,
                'class_name' => SimpleClient::class,
                'service_constructor_argument_length' => 3,
                'constructor_definitions' => [],
                'x_api_token_argument_index' => 1,
                'guzzle_config_argument_index' => 2,
                'expected_guzzle_config' => $guzzleConfigurationBase,
                'service_classification' => 'simple',
            ],
            'basic' => [
                'configuration' => $basicConfiguration,
                'class_name' => BasicClient::class,
                'service_constructor_argument_length' => 5,
                'constructor_definitions' => [],
                'x_api_token_argument_index' => 3,
                'guzzle_config_argument_index' => 4,
                'expected_guzzle_config' => $guzzleConfigurationBase,
                'service_classification' => 'basic',
            ],
            'jwt' => [
                'configuration' => $jwtConfiguration,
                'class_name' => JwtClient::class,
                'service_constructor_argument_length' => 4,
                'constructor_definitions' => [],
                'x_api_token_argument_index' => 2,
                'guzzle_config_argument_index' => 3,
                'expected_guzzle_config' => $guzzleConfigurationBase,
                'service_classification' => 'jwt',
            ],
            'aws' => [
                'configuration' => $awsConfiguration,
                'class_name' => AwsApiGatewayClient::class,
                'service_constructor_argument_length' => 5,
                'constructor_definitions' => [
                    1 => SignatureV4::class,
                    2 => Credentials::class,
                ],
                'x_api_token_argument_index' => 3,
                'guzzle_config_argument_index' => 4,
                'expected_guzzle_config' => $guzzleConfigurationBase,
                'service_classification' => 'aws',
            ],
        ];
    }

    /**
     * I know this looks like a wonky, integration type test, but because the ContainerBuilder is really
     * a POPO with some factor magic - we really don't need to worry about mocking it, and can rely of tracking
     * its Definition classes (the proxies that exist before an actual service is created).
     *
     * Sincerely,
     *  - mgmt
     *
     * @dataProvider extensionWillSetExpectedServiceDataProvider
     *
     * @param array  $configuration                         The config to pass into our Extension class
     * @param string $expectedClassName                     The class-name of the service to be built
     * @param int    $expectedConstructorArgumentListLength The number of arguments expected to be passed to our class
     * @param array  $expectedConstructorDefinitions        For any arguments that are Definitions, verify class names
     * @param int    $expectedXApiKeyArgumentIndex          Which constructor argument index is the API key
     * @param int    $expectedGuzzleConfigArgumentIndex     Which constructor argument index is the guzzle config
     * @param array  $expectedGuzzleConfig                  What we expect the guzzle config to be upon instantiation
     * @param string $serviceClassification                 A key [simple, jwt, aws] that classifies the client
     */
    public function testExtensionWillSetExpectedService(
        array $configuration,
        $expectedClassName,
        $expectedConstructorArgumentListLength,
        array $expectedConstructorDefinitions,
        $expectedXApiKeyArgumentIndex,
        $expectedGuzzleConfigArgumentIndex,
        array $expectedGuzzleConfig,
        $serviceClassification
    ) {
        $containerBuilder = new ContainerBuilder();

        $this->systemUnderTest->load(
            $configuration,
            $containerBuilder
        );

        $clients = ['test', 'derp'];
        $this->assertCount(count($clients), $containerBuilder->getDefinitions());

        foreach ($clients as $client) {
            $serviceKey = sprintf('nab.web_api_sdk.%s.%s_client', $client, $serviceClassification);
            // Assert we still have a service definition.
            $definition = $containerBuilder->getDefinition($serviceKey);
            $this->assertInstanceOf(
                'Symfony\Component\DependencyInjection\Definition',
                $definition,
                'Failed to assert service is instance of Definition.'
            );

            $this->assertSame($expectedClassName, $definition->getClass());

            // Assert that the service definition was created with 3 arguments.
            $this->assertCount(
                $expectedConstructorArgumentListLength,
                $definition->getArguments(),
                'Failed to assert the expected number of constructor arguments.'
            );

            // Assert, if expected, that other Definition classes were created for construct.
            if ($expectedConstructorDefinitions) {
                foreach ($expectedConstructorDefinitions as $index => $className) {
                    /** @var Definition */
                    $argumentDefinition = $definition->getArguments()[$index];
                    $this->assertSame(
                        $argumentDefinition->getClass(),
                        $className,
                        'Failed asserting other constructor args are Definitions for ' . $serviceClassification . '.'
                    );
                }
            }

            // Almost there, assert the api key was set
            $this->assertSame(
                'abc',
                $definition->getArgument($expectedXApiKeyArgumentIndex),
                'Failed asserting api key was set as expected.'
            );

            // Lastly,
            $guzzleConfiguration = $definition->getArgument($expectedGuzzleConfigArgumentIndex);

            ksort($expectedGuzzleConfig);
            ksort($guzzleConfiguration);

            $this->assertSame(
                $expectedGuzzleConfig,
                $guzzleConfiguration,
                'Failed asserting guzzle config was set as expected.'
            );
        }
    }

    public function testExtensionIsDefiningExpectedAlias()
    {
        $expected = 'nab_web_api_base_sdk';
        $this->assertSame($expected, $this->systemUnderTest->getAlias());
    }

    public function testBaseSdkCanBeWrapped()
    {
        $configuration = [
            'nab_web_api_base_sdk' => [
                'guzzle_configuration' => [],
                'endpoints' => [
                    'test' => [
                        'wrapped_by' => NABWebApiBaseSdkExtension::class,
                        'base_endpoint' => 'test.com',
                        'api_key' => 'abc',
                    ],
                    'derp' => [
                        'wrapped_by' => Configuration::class,
                        'base_endpoint' => 'derp.com',
                        'api_key' => 'abc',
                    ]
                ]
            ]
        ];

        $containerBuilder = new ContainerBuilder();

        $this->systemUnderTest->load(
            $configuration,
            $containerBuilder
        );

        foreach (['test', 'derp'] as $endpoint) {
            $definitionKey = sprintf('nab.web_api_sdk.%s.simple_client', $endpoint);
            $this->assertTrue(
                $containerBuilder->hasDefinition($definitionKey)
            );

            $definition = $containerBuilder->getDefinition($definitionKey);
            $this->assertSame(
                $configuration['nab_web_api_base_sdk']['endpoints'][$endpoint]['wrapped_by'],
                $definition->getClass()
            );
        }
    }

    public function testBaseSdkIsNotWrappedByDefault()
    {
        $configuration = [
            'nab_web_api_base_sdk' => [
                'guzzle_configuration' => [],
                'endpoints' => [
                    'test' => [
                        'base_endpoint' => 'test.com',
                        'api_key' => 'abc',
                    ],
                    'derp' => [
                        'base_endpoint' => 'derp.com',
                        'api_key' => 'abc',
                    ]
                ]
            ]
        ];

        $containerBuilder = new ContainerBuilder();

        $this->systemUnderTest->load(
            $configuration,
            $containerBuilder
        );

        foreach (['test', 'derp'] as $endpoint) {
            $definitionKey = sprintf('nab.web_api_sdk.%s.simple_client', $endpoint);
            $this->assertTrue(
                $containerBuilder->hasDefinition($definitionKey)
            );

            $definition = $containerBuilder->getDefinition($definitionKey);
            $this->assertSame(
                SimpleClient::class,
                $definition->getClass()
            );
        }
    }
}
