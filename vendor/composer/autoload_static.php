<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit839d43be41e4db3078673ec2935e720e
{
    public static $files = array (
        'ad155f8f1cf0d418fe49e248db8c661b' => __DIR__ . '/..' . '/react/promise/src/functions_include.php',
        '972fda704d680a3a53c68e34e193cb22' => __DIR__ . '/..' . '/react/promise-timer/src/functions_include.php',
        'cea474b4340aa9fa53661e887a21a316' => __DIR__ . '/..' . '/react/promise-stream/src/functions_include.php',
        'ebf8799635f67b5d7248946fe2154f4a' => __DIR__ . '/..' . '/ringcentral/psr7/src/functions_include.php',
        '0e6d7bf4a5811bfa5cf40c5ccd6fae6a' => __DIR__ . '/..' . '/symfony/polyfill-mbstring/bootstrap.php',
        '5b6d49eb231faf64eed5b5de9df7aa98' => __DIR__ . '/..' . '/ccxt/ccxt/ccxt.php',
    );

    public static $prefixLengthsPsr4 = array (
        'c' => 
        array (
            'ccxt_async\\' => 11,
            'ccxt\\' => 5,
        ),
        'S' => 
        array (
            'Symfony\\Polyfill\\Mbstring\\' => 26,
        ),
        'R' => 
        array (
            'RingCentral\\Psr7\\' => 17,
            'Recoil\\React\\' => 13,
            'Recoil\\Kernel\\' => 14,
            'Recoil\\' => 7,
            'React\\Stream\\' => 13,
            'React\\Socket\\' => 13,
            'React\\Promise\\Timer\\' => 20,
            'React\\Promise\\Stream\\' => 21,
            'React\\Promise\\' => 14,
            'React\\Http\\' => 11,
            'React\\EventLoop\\' => 16,
            'React\\Dns\\' => 10,
            'React\\Cache\\' => 12,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
        ),
        'I' => 
        array (
            'Icecave\\Repr\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ccxt_async\\' => 
        array (
            0 => __DIR__ . '/..' . '/ccxt/ccxt/php/async',
        ),
        'ccxt\\' => 
        array (
            0 => __DIR__ . '/..' . '/ccxt/ccxt/php',
        ),
        'Symfony\\Polyfill\\Mbstring\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-mbstring',
        ),
        'RingCentral\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/ringcentral/psr7/src',
        ),
        'Recoil\\React\\' => 
        array (
            0 => __DIR__ . '/..' . '/recoil/react/src',
        ),
        'Recoil\\Kernel\\' => 
        array (
            0 => __DIR__ . '/..' . '/recoil/kernel/src',
        ),
        'Recoil\\' => 
        array (
            0 => __DIR__ . '/..' . '/recoil/api/src',
        ),
        'React\\Stream\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/stream/src',
        ),
        'React\\Socket\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/socket/src',
        ),
        'React\\Promise\\Timer\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/promise-timer/src',
        ),
        'React\\Promise\\Stream\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/promise-stream/src',
        ),
        'React\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/promise/src',
        ),
        'React\\Http\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/http/src',
        ),
        'React\\EventLoop\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/event-loop/src',
        ),
        'React\\Dns\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/dns/src',
        ),
        'React\\Cache\\' => 
        array (
            0 => __DIR__ . '/..' . '/react/cache/src',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'Icecave\\Repr\\' => 
        array (
            0 => __DIR__ . '/..' . '/icecave/repr/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'E' => 
        array (
            'Evenement' => 
            array (
                0 => __DIR__ . '/..' . '/evenement/evenement/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'Console_Table' => __DIR__ . '/..' . '/pear/console_table/Table.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit839d43be41e4db3078673ec2935e720e::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit839d43be41e4db3078673ec2935e720e::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit839d43be41e4db3078673ec2935e720e::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit839d43be41e4db3078673ec2935e720e::$classMap;

        }, null, ClassLoader::class);
    }
}
