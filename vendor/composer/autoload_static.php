<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit0d9ce4094831fab91521874725e87724
{
    public static $files = array (
        '7b11c4dc42b3b3023073cb14e519683c' => __DIR__ . '/..' . '/ralouphie/getallheaders/src/getallheaders.php',
        '6e3fae29631ef280660b3cdad06f25a8' => __DIR__ . '/..' . '/symfony/deprecation-contracts/function.php',
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
        'b067bc7112e384b61c701452d53a14a8' => __DIR__ . '/..' . '/mtdowling/jmespath.php/src/JmesPath.php',
        '8a9dc1de0ca7e01f3e08231539562f61' => __DIR__ . '/..' . '/aws/aws-sdk-php/src/functions.php',
        'decc78cc4436b1292c6c0d151b19445c' => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'p' => 
        array (
            'phpseclib3\\' => 11,
        ),
        'S' => 
        array (
            'Symfony\\Polyfill\\Mbstring\\' => 26,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
            'Psr\\Http\\Client\\' => 16,
            'ParagonIE\\ConstantTime\\' => 23,
        ),
        'J' => 
        array (
            'JmesPath\\' => 9,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
        ),
        'D' => 
        array (
            'DataExport\\Services\\' => 20,
            'DataExport\\Helpers\\' => 19,
            'DataExport\\Commands\\' => 20,
            'DataExport\\' => 11,
        ),
        'A' => 
        array (
            'Aws\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'phpseclib3\\' => 
        array (
            0 => __DIR__ . '/..' . '/phpseclib/phpseclib/phpseclib',
        ),
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-factory/src',
            1 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'Psr\\Http\\Client\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-client/src',
        ),
        'ParagonIE\\ConstantTime\\' => 
        array (
            0 => __DIR__ . '/..' . '/paragonie/constant_time_encoding/src',
        ),
        'JmesPath\\' => 
        array (
            0 => __DIR__ . '/..' . '/mtdowling/jmespath.php/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'DataExport\\Services\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Services',
        ),
        'DataExport\\Helpers\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Helpers',
        ),
        'DataExport\\Commands\\' => 
        array (
            0 => __DIR__ . '/../..' . '/Commands',
        ),
        'DataExport\\' => 
        array (
            0 => __DIR__ . '/../..' . '/',
        ),
        'Aws\\' => 
        array (
            0 => __DIR__ . '/..' . '/aws/aws-sdk-php/src',
        ),
    );

    public static $classMap = array (
        'AWS\\CRT\\Auth\\AwsCredentials' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Auth/AwsCredentials.php',
        'AWS\\CRT\\Auth\\CredentialsProvider' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Auth/CredentialsProvider.php',
        'AWS\\CRT\\Auth\\Signable' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Auth/Signable.php',
        'AWS\\CRT\\Auth\\SignatureType' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Auth/SignatureType.php',
        'AWS\\CRT\\Auth\\SignedBodyHeaderType' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Auth/SignedBodyHeaderType.php',
        'AWS\\CRT\\Auth\\Signing' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Auth/Signing.php',
        'AWS\\CRT\\Auth\\SigningAlgorithm' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Auth/SigningAlgorithm.php',
        'AWS\\CRT\\Auth\\SigningConfigAWS' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Auth/SigningConfigAWS.php',
        'AWS\\CRT\\Auth\\SigningResult' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Auth/SigningResult.php',
        'AWS\\CRT\\Auth\\StaticCredentialsProvider' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Auth/StaticCredentialsProvider.php',
        'AWS\\CRT\\CRT' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/CRT.php',
        'AWS\\CRT\\HTTP\\Headers' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/HTTP/Headers.php',
        'AWS\\CRT\\HTTP\\Message' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/HTTP/Message.php',
        'AWS\\CRT\\HTTP\\Request' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/HTTP/Request.php',
        'AWS\\CRT\\HTTP\\Response' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/HTTP/Response.php',
        'AWS\\CRT\\IO\\EventLoopGroup' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/IO/EventLoopGroup.php',
        'AWS\\CRT\\IO\\InputStream' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/IO/InputStream.php',
        'AWS\\CRT\\Internal\\Encoding' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Internal/Encoding.php',
        'AWS\\CRT\\Internal\\Extension' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Internal/Extension.php',
        'AWS\\CRT\\Log' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Log.php',
        'AWS\\CRT\\NativeResource' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/NativeResource.php',
        'AWS\\CRT\\OptionValue' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Options.php',
        'AWS\\CRT\\Options' => __DIR__ . '/..' . '/aws/aws-crt-php/src/AWS/CRT/Options.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit0d9ce4094831fab91521874725e87724::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit0d9ce4094831fab91521874725e87724::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit0d9ce4094831fab91521874725e87724::$classMap;

        }, null, ClassLoader::class);
    }
}