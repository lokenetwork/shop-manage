<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitaad42a9986530bbccb348bccdbf66b8d
{
    public static $files = array (
        '2cffec82183ee1cea088009cef9a6fc3' => __DIR__ . '/..' . '/ezyang/htmlpurifier/library/HTMLPurifier.composer.php',
    );

    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'L' => 
        array (
            'Lcobucci\\JWT\\' => 13,
        ),
        'K' => 
        array (
            'Katzgrau\\KLogger\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Lcobucci\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/lcobucci/jwt/src',
        ),
        'Katzgrau\\KLogger\\' => 
        array (
            0 => __DIR__ . '/..' . '/katzgrau/klogger/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'H' => 
        array (
            'HTMLPurifier' => 
            array (
                0 => __DIR__ . '/..' . '/ezyang/htmlpurifier/library',
            ),
        ),
    );

    public static $classMap = array (
        'Katzgrau\\KLogger\\Logger' => __DIR__ . '/..' . '/katzgrau/klogger/src/Logger.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitaad42a9986530bbccb348bccdbf66b8d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitaad42a9986530bbccb348bccdbf66b8d::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitaad42a9986530bbccb348bccdbf66b8d::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitaad42a9986530bbccb348bccdbf66b8d::$classMap;

        }, null, ClassLoader::class);
    }
}
