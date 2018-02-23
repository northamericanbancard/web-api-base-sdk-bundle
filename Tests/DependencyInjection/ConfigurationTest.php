<?php

/**
 * Filename:    ConfigurationTest.php
 * Created:     14/02/18, at 6:00 PM
 * @author      James Hollenbeck <jhollenbeck@nabancard.com>
 * @copyright   1992-2018 North American Bancard
 */

namespace NAB\Bundle\WebApiBaseSdk\Tests\DependencyInjection;

use NAB\Bundle\WebApiBaseSdk\DependencyInjection\Configuration;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ConfigurationTest extends KernelTestCase
{
    /**
     * @dataProvider configurationCanControlGuzzleConfigDataProvider
     *
     * @param array $guzzleConfigSupplied
     * @param array $expected
     */
    public function testConfigurationCanControlGuzzleConfig(array $guzzleConfigSupplied, array $expected)
    {
        $treeBuilder = (new Configuration())->getConfigTreeBuilder();
        $tree = $treeBuilder->buildTree();
        $finalConfig = $tree->finalize($tree->normalize($guzzleConfigSupplied));
        $finalConfig = $finalConfig['guzzle_configuration'];

        ksort($expected);
        ksort($finalConfig);

        $this->assertSame($expected, $finalConfig);
    }

    /**
     * @return array
     */
    public function configurationCanControlGuzzleConfigDataProvider()
    {
        return [
            [
                'input' => [
                    'guzzle_configuration' => []
                ],
                'expected' => [
                    'http_errors' => false,
                    'decode_content' => true,
                    'verify' => true,
                    'cookies' => false,
                ]
            ],
            [
                'input' => [
                    'guzzle_configuration' => [
                        'http_errors' => true,
                        'cookies' => true,
                    ]
                ],
                'expected' => [
                    'http_errors' => true,
                    'decode_content' => true,
                    'verify' => true,
                    'cookies' => true,
                ]
            ]
        ];
    }
}
