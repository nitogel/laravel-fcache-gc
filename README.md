# Laravel file cache garbage collector

When using the file cache driver, Laravel creates the cache files but never purges expired ones. This can lead to
a situation where you have a large number of unused and irrelevant cache files, especially if you do a lot of short-term
caching in your system.

This package creates an artisan command cache:gc that will garbage-collect your cache files, removing any that have expired.
You may run this manually or include it in a schedule.

Thanks to Jon Baker for inspire [https://github.com/jdavidbakr/laravel-cache-garbage-collector]

## Install

Via Composer

``` bash
$ composer require nitogel/laravel-fcache-gc
```

Then add the service provider to `app/Console/Kernel.php` in the $commands array:

``` php
\Nitogel\LaravelFileCacheGarbageCollector\ClearExpiredCache::class
```

## Usage

``` bash
$ php artisan cache:gc 
```
``` bash
$ php artisan cache:gc -d -i 
```

## Params
```-d``` deleting folders
```-i``` interactive deleting