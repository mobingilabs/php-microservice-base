# How to start:

```bash
$ composer create-project mobingilabs/php-microservice-base your_service_name_here
```
Follow the composer instructions and it will generate the project using data provided in the wizard.

# Expressive Skeleton 3 - [SERVICE_NAME] Micro-service 

## Application Development Mode Tool

This skeleton comes with [zf-development-mode](https://github.com/zfcampus/zf-development-mode). 
It provides a composer script to allow you to enable and disable development mode.

### To enable development mode

**Note:** Do NOT run development mode on your production server!

```bash
$ composer development-enable
```

**Note:** Enabling development mode will also clear your configuration cache, to 
allow safely updating dependencies and ensuring any new configuration is picked 
up by your application.

### To disable development mode

```bash
$ composer development-disable
```

### Development mode status

```bash
$ composer development-status
```

## Configuration caching

By default, the skeleton will create a configuration cache in
`data/config-cache.php`. When in development mode, the configuration cache is
disabled, and switching in and out of development mode will remove the
configuration cache.

You may need to clear the configuration cache in production when deploying if
you deploy to the same directory. You may do so using the following:

```bash
$ composer clear-config-cache
```

You may also change the location of the configuration cache itself by editing
the `config/config.php` file and changing the `config_cache_path` entry of the
local `$cacheConfig` variable.

## Useful Information

[Mobing API Design](https://github.com/mobingilabs/microservice-docs/wiki/API-Design)

[Zend Expressive Documentation](https://docs.zendframework.com/zend-expressive/)

[Zend Expressive Cookbook](https://www.gitbook.com/book/zfdevteam/expressive-cookbook/details)

[12 Factor (English)](https://12factor.net/) - [12 Factor (日本語)](https://12factor.net/ja/)

[REST API Checklist](http://www.kennethlange.com/posts/The-Ultimate-Checklist-for-REST-APIs.html)