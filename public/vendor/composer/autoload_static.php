<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit35255328f82e336db879619150868e0c
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'Workerman\\' => 10,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Workerman\\' => 
        array (
            0 => __DIR__ . '/..' . '/workerman/workerman',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit35255328f82e336db879619150868e0c::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit35255328f82e336db879619150868e0c::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit35255328f82e336db879619150868e0c::$classMap;

        }, null, ClassLoader::class);
    }
}
