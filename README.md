# Web API Base SDK Bundle

Do you have an SDK library based off of our [web-api-base-sdk-php](https://github.com/northamericanbancard/web-api-base-sdk-php) 
package and wish to turn it into a Symfony service?

This 3rd Party Bundle will allow you to quickly create one or more `SDK`s as a service. You can choose to supply
either AWS IAM Credentials, a JWT token, or if there is no authentication required, no credentials for a `Simple SDK`.

If you have a library of one or more SDKs, where each SDK talks to a single API, and each SDK takes as
a single dependency a base client from [web-api-base-sdk-php](https://github.com/northamericanbancard/web-api-base-sdk-php) -
by creating your own Symfony 3rd Party Bundle and requiring this bundle and using the instructions below:
[Specialized Bundle](#specialized-bundle), you will be able to quickly turn your SDKs into Symfony Services with ease.

*_NOTE_*:

Even though the name of this bundle is the `web-api-base-sdk-bundle`, treat the word `base` as a bit of a misnomer. If you
just need a quick SDK to call out to endpoints without creating a specialized SDK bundle backed by this bundle, you are certainly
free to do so! Just follow the instructions under [Non-specialized Bundle](#non-specialized-bundle)

## Getting Started

Follow the below instructions in order to successfully include and utilize this bundle within your project.

### Prerequisites

Ensure you are running PHP >= 5.5 and Symfony >= 2.8. Any endpoints you are calling that are secured
via IAM, you will have to obtain the IAM user's access key and secret key. If required, you should
also obtain the `x-api-key` header's API key (found in API Gateway).  Any endpoints secured using JWT, will
require the JWT to be obtained as well.

The `x-api-key` header can be used with any SDK you create, giving you a consistent way to pass across an API key, regardless
of your authentication scheme - especially as your `Authorization: Bearer` header is already used for JWT.

## Installing

### Symfony

*_NOTE_*:

With either bundle architecture you use, you have the option to have your endpoint use JWT, AWS (IAM), 
or nothing as your primary authentication mechanism. Each option allows you to use an x-api-key header if you wish, 
for sending api keys (not generally used as anything more than to determine who the user is and track account details). 
JWT-based SDKs send requests with the header `Authorization: Bearer my.tok.en`

#### Non-specialized Bundle

1. Include this bundle in your Symfony project by running `composer require nab/web-api-base-sdk-bundle`.
2. Register the bundle with Symfony by editing your app/AppKernel.php: `new \NAB\Bundle\NAB\Bundle\WebApiBaseSdk\NABWebApiBaseSdkBundle(),`
3. update your app/config/config_* files with the proper configuration as noted below:

#### AWS

```yaml
nab_web_api_base_sdk:
  guzzle_configuration:
    http_errors: <DEFAULT:false>
    decode_content: <DEFAULT:true>
    verify: <DEFAULT:true>
    cookies: <DEFAULT:false>
  endpoints:
    my_endpoint_name:
      base_endpoint: <REQUIRED> #base path of your api such has https://my.api.aws.com/api/stage, no trailing /
      api_key: <DEFAULT:NULL>
      aws:
        aws_region: <DEFAULT:us-east-1>
        aws_service: <DEFAULT:execute-api>
        credentials:
          access_key: <IAM_USER_ACCESS_KEY_ID:REQUIRED>
          secret_key: <IAM_USER_SECRET_KEY:REQUIRED>
```

#### JWT

```yaml
nab_web_api_base_sdk:
  guzzle_configuration:
    http_errors: <DEFAULT:false>
    decode_content: <DEFAULT:true>
    verify: <DEFAULT:true>
    cookies: <DEFAULT:false>
  endpoints:
    my_endpoint_name:
      base_endpoint: <REQUIRED> #base path of your api such has https://my.api.aws.com/api/stage, no trailing /
      api_key: <DEFAULT:NULL>
      jwt:
        token: <MY_TOKEN:REQUIRED>
```

#### Simple

```yaml
nab_web_api_base_sdk:
  guzzle_configuration:
    http_errors: <DEFAULT:false>
    decode_content: <DEFAULT:true>
    verify: <DEFAULT:true>
    cookies: <DEFAULT:false>
  endpoints:
    my_endpoint_name:
      base_endpoint: <REQUIRED> #base path of your api such has https://my.api.aws.com/api/stage, no trailing /
      api_key: <DEFAULT:NULL>
      #Note - No authentication mechanism listed (such as jwt or aws).
```

Obtain the service using the service key `nab.web_api_sdk.<my_endpoint_name>.[aws|jwt|simple]_client` (depending on authentication scheme), and make a request
using the one of the following API calls:

#### API Calls:

The allowed calls you can make on the service are noted as:

1. httpGet(string $path, array $queryParams = [], array $headers = [], $body = null)
2. httpPost(string $path, array $queryParams = [], array $headers = [], $body = null)

*_NOTE_*: If your Guzzle configuration allows http_errors (true), the the above functions will throw
exceptions if you do not get 200 response codes. Review Guzzle's documentation regarding exceptions.

Example Calls:

```php
// Note the first '/' in the path (argument 1).
$service->httpGet('/foop/1', ['foo' => 'bar', 'baz' => 'bing'], ['Accept' => 'application/json'])
$service->httpPost('/bap', ['foo' => 'bar', 'baz' => 'bing'], ['Accept' => 'application/json'], '{"a": "b"}')
```

**_NOTE_** The client service is an extension of `GuzzleHttp\Client`, and therefore returns an instance of
`\Psr\Http\Message\ResponseInterface`, or throws an exception on failed requests.

#### Specialized Bundle

When including this bundle into your specialized bundle, do NOT register this bundle in your AppKernel. Instead,
inherit from our `DependencyInjection/(Configuration)&(NABWebApiBaseSdkExtension)`. One you've done that in your
config, override the extension's const `CONFIGURATION_ALIAS` variable in your extension class to be the alias you need 
for your bundle's configuration. Make the following addition to your `Configuration` class:

```php
// DependencyInjection\Configuration
/**
 * This method is used to get the root key for the configuration.
 *
 * {@see NABWebApiBaseSdkExtension::getAlias}
 *
 * @return string
 */
protected function getConfigurationRootKey()
{
    return (new MyBundleExtension())->getAlias();
}
```

Now, in order for Symfony to process your config's root key, add the following to your Extension file:

```php
/**
 * Overrides the parent configuration instance.
 *
 * @return Configuration
 */
protected function getConfigurationInstance()
    {
        return new Configuration();
    }
```

Now, add your endpoints as you would using your own bundles' config alias. The SDK classes that should wrap the
NAB Web Api Base SDK library's clients (such as JwtClient, AwsApiGatewayClient...etc), are specified using the config for `wrapped_by`.
If you omit the `getConfigurationInstance` from above, you will not have your bundle's config key, and it will instead be
`nab_web_api_base_sdk`.

If you need to add extra config options to what already exists, you can append them using the following in your
`Configuration` class:

```php
public function getConfigTreeBuilder()
    {
        $treeBuilder = parent::getConfigTreeBuilder();

        //protected attribute access workaround
        $reflectedClass = new \ReflectionObject($treeBuilder);
        $property = $reflectedClass->getProperty("root");
        $property->setAccessible(true);

        // Grab the rootnode from the actual existing TreeBuilder instance.
        $rootNode = $property->getValue($treeBuilder);

        // Append your config here.
        $rootNode
           ->children()
           ...

        return $treeBuilder;
     }
```

#### AWS

```yaml
my_special_config_alias:
  guzzle_configuration:
    http_errors: <DEFAULT:false>
    decode_content: <DEFAULT:true>
    verify: <DEFAULT:true>
    cookies: <DEFAULT:false>
  endpoints:
    my_endpoint_name:
      wrapped_by: My\Sdk\Class  #Either set this as YOUR SDK wrapper around the BaseSDK Client in your bundle by default during config load in your extension, or here, or leave empty.
      base_endpoint: <REQUIRED> #base path of your api such has https://my.api.aws.com/api/stage, no trailing /
      api_key: <DEFAULT:NULL>
      aws:
        aws_region: <DEFAULT:us-east-1>
        aws_service: <DEFAULT:execute-api>
        credentials:
          access_key: <IAM_USER_ACCESS_KEY_ID:REQUIRED>
          secret_key: <IAM_USER_SECRET_KEY:REQUIRED>
```

#### JWT

```yaml
nab_web_api_base_sdk:
  guzzle_configuration:
    http_errors: <DEFAULT:false>
    decode_content: <DEFAULT:true>
    verify: <DEFAULT:true>
    cookies: <DEFAULT:false>
  endpoints:
    my_endpoint_name:
      wrapped_by: My\Sdk\Class  #Either set this as YOUR SDK wrapper around the BaseSDK Client in your bundle by default during config load in your extension, or here, or leave empty.
      base_endpoint: <REQUIRED> #base path of your api such has https://my.api.aws.com/api/stage, no trailing /
      api_key: <DEFAULT:NULL>
      jwt:
        token: <MY_TOKEN:REQUIRED>
```

#### Basic

```yaml
nab_web_api_base_sdk:
  guzzle_configuration:
    http_errors: <DEFAULT:false>
    decode_content: <DEFAULT:true>
    verify: <DEFAULT:true>
    cookies: <DEFAULT:false>
  endpoints:
    my_endpoint_name:
      wrapped_by: My\Sdk\Class  #Either set this as YOUR SDK wrapper around the BaseSDK Client in your bundle by default during config load in your extension, or here, or leave empty.
      base_endpoint: <REQUIRED> #base path of your api such has https://my.api.aws.com/api/stage, no trailing /
      api_key: <DEFAULT:NULL>
      basic:
        username: <MY_HTTP_BASIC_AUTH_USERNAME:REQUIRED>
        password: <MY_HTTP_BASIC_AUTH_PASSWORD:REQUIRED>
```

#### Simple

```yaml
nab_web_api_base_sdk:
  guzzle_configuration:
    http_errors: <DEFAULT:false>
    decode_content: <DEFAULT:true>
    verify: <DEFAULT:true>
    cookies: <DEFAULT:false>
  endpoints:
    my_endpoint_name:
      wrapped_by: My\Sdk\Class  #Either set this as YOUR SDK wrapper around the BaseSDK Client in your bundle by default during config load in your extension, or here, or leave empty.
      base_endpoint: <REQUIRED> #base path of your api such has https://my.api.aws.com/api/stage, no trailing /
      api_key: <DEFAULT:NULL>
      #Note - No authentication mechanism listed (such as jwt or aws).
```

#### API Calls

This is entirely dependent on how you decided to wrap the calls found under the Non-Specialized version.

## Development

**_First Install/Make Introduction_**

1.  Ensure you have autoconf (v2.69 at min) installed.
2.  In the project root, run `make` to install your new project.
3.  View the Makefile, or Makefile.in for further commands and required inputs.
4.  Any edits to Makefile should be done in Makefile.in and then run `autoconf && ./configure && make`

## Testing and Maintenance

The following command should be run often, and should be run during every code review:

```bash
make test
```

OR

```bash
make docker-test
```

OR

```bash
make php
```

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/northamericanbancard/web-api-base-sdk-bundle/tags). 

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
