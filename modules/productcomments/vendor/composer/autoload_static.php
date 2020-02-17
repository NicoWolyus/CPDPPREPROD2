<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit59666a9bbf5f822435f259714d1f3a15
{
    public static $files = array (
        'ad155f8f1cf0d418fe49e248db8c661b' => __DIR__ . '/..' . '/react/promise/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Component\\CssSelector\\' => 30,
        ),
        'R' => 
        array (
            'React\\Promise\\' => 14,
        ),
        'P' => 
        array (
            'PrestaShop\\Module\\ProductComment\\' => 33,
            'PrestaShop\\CircuitBreaker\\' => 26,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Subscriber\\Cache\\' => 28,
            'GuzzleHttp\\Stream\\' => 18,
            'GuzzleHttp\\Ring\\' => 16,
            'GuzzleHttp\\' => 11,
        ),
        'D' => 
        array (
            'Doctrine\\Common\\Cache\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Component\\CssSelector\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/css-selector',
        ),
        'React\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/promise/src',
        ),
        'PrestaShop\\Module\\ProductComment\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'PrestaShop\\CircuitBreaker\\' => 
        array (
            0 => __DIR__ . '/..' . '/prestashop/circuit-breaker/src',
        ),
        'GuzzleHttp\\Subscriber\\Cache\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/cache-subscriber/src',
        ),
        'GuzzleHttp\\Stream\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/streams/src',
        ),
        'GuzzleHttp\\Ring\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/ringphp/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'Doctrine\\Common\\Cache\\' => 
        array (
            0 => __DIR__ . '/..' . '/doctrine/cache/lib/Doctrine/Common/Cache',
        ),
    );

    public static $classMap = array (
        'ProductComments' => __DIR__ . '/../..' . '/productcomments.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit59666a9bbf5f822435f259714d1f3a15::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit59666a9bbf5f822435f259714d1f3a15::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit59666a9bbf5f822435f259714d1f3a15::$classMap;

        }, null, ClassLoader::class);
    }
}