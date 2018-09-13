<?php

declare(strict_types=1);

namespace App;

use Composer\Script\Event;

class Script
{
    const FILES_TO_REPLACE_RESOURCE = [
        './config/routes.php',
        './src/App/Factory/ExampleFactory.php',
        './src/App/Handler/ExampleHandler.php',
        './src/App/Middleware/ValidationMiddleware.php',
        './src/App/Model/ExampleModel.php',
        './src/App/Validation/ExampleCreateSchema.json',
        './src/App/Validation/ExampleUpdateSchema.json',
        './src/App/ConfigProvider.php',
    ];

    const FILES_TO_REPLACE_SERVICE = [
        './.circleci/config.yml',
        './src/App/Factory/AbstractFactory.php',
        './src/App/Validation/ExampleCreateSchema.json',
        './src/App/Validation/ExampleUpdateSchema.json',
        './src/App/docker_image_config.xml',
        './README.md',
    ];

    const FILES_TO_RENAME = [
        './src/App/Factory/ExampleFactory.php',
        './src/App/Handler/ExampleHandler.php',
        './src/App/Model/ExampleModel.php',
        './src/App/Validation/ExampleCreateSchema.json',
        './src/App/Validation/ExampleUpdateSchema.json',
    ];

    public static function install(Event $event): void
    {
        $answers['Service_Name']    = $event->getIO()->ask("\n  <question>>>> What is the 'micro-service' name?</question> [default: <comment>my service</comment>] ", 'my service');
        $answers['Resource_Name']   = $event->getIO()->ask("\n  <question>>>> What is the 'resource' name?</question> [default: <comment>my resource</comment>] ", 'my resource');
        $answers['PHPStorm_Config'] = $event->getIO()->askConfirmation("\n  <question>>>> Are you using PHPStorm? Do you want to auto add Docker image config?</question> (y/n) [default: <comment>yes</comment>] ", true);
        if ($answers['PHPStorm_Config']) {
            $event->getIO()->write("\n  <info>>>> Please provide your AWS credentials</info>");
            $answers['Access_Key_ID']     = $event->getIO()->ask("\n  <question>>>> Access Key ID:</question> ", 'not-provided');
            $answers['Secret_Access_Key'] = $event->getIO()->ask("\n  <question>>>> Secret Access Key:</question> ", 'not-provided');
            $answers['PHPStorm_Config']   = 'yes';
        } else {
            unset($answers['PHPStorm_Config']);
        }

        $event->getIO()->write("\n  ::: Your current configuration:");
        $event->getIO()->write("  -------------------------------");
        foreach ($answers as $key => $answer) {
            $event->getIO()->write("  ::: {$key}: <info>{$answer}</info>");
        }
        $start = $event->getIO()->askConfirmation("\n  <question>>>> Do you want to proceed?</question> (y/n) [default: <comment>yes</comment>] ", true);

        if (!$start) {
            $event->getIO()->write("\n  <warning>>>> Canceling...</warning>");
            exit(0);
        }

        self::replaceFilesContentResource($answers['Resource_Name']);
        self::replaceFilesContentService($answers['Service_Name']);
        self::renameFiles($answers['Resource_Name']);

        if (isset($answers['PHPStorm_Config'])) {
            $event->getIO()->write("\n  <info>>>> Creating PHPStorm Configurations...</info>");
            self::addPHPStormConfig($answers);
        }

        self::finishScript();
    }

    private static function renameFiles($resourceName)
    {
        $resourceName = self::toCamelCase($resourceName);
        $resourceName = ucfirst($resourceName);
        foreach (self::FILES_TO_RENAME as $file) {
            rename($file, str_replace('Example', $resourceName, $file));
        }
    }

    private static function replaceFilesContentResource($resourceName)
    {
        $resourceName   = self::toCamelCase($resourceName);
        $ucFirstName    = ucfirst($resourceName);
        $underscoreName = self::toUnderscore($resourceName);
        foreach (self::FILES_TO_REPLACE_RESOURCE as $file) {
            $content = file_get_contents($file);
            $content = str_replace('Example', $ucFirstName, $content);
            $content = str_replace('example_id', "{$underscoreName}_id", $content);
            $content = str_replace('/example', '/' . str_replace('_', '-', $underscoreName), $content);
            $content = str_replace('example', $resourceName, $content);
            file_put_contents($file, $content);
        }
    }

    private static function replaceFilesContentService($serviceName)
    {
        $serviceName = self::toCamelCase($serviceName);
        $serviceName = self::toUnderscore($serviceName);
        $serviceName = str_replace('_', '-', $serviceName);
        foreach (self::FILES_TO_REPLACE_SERVICE as $file) {
            $content = file_get_contents($file);
            $content = str_replace('SERVICE_NAME', $serviceName, $content);
            file_put_contents($file, $content);
        }
    }

    private static function finishScript()
    {
        $search = '
        "pre-install-cmd": "App\\\\Script::install",';

        $composerJson = file_get_contents('./composer.json');
        $composerJson = str_replace($search, '', $composerJson);
        file_put_contents('./composer.json', $composerJson);

        $search = '# How to start:

```bash
$ composer create-project mobingilabs/php-microservice-base your_service_name_here
```
Follow the composer instructions and it will generate the project using data provided in the wizard.

';

        $readme = file_get_contents('./README.md');
        $readme = str_replace($search, '', $readme);
        file_put_contents('./README.md', $readme);

        unlink('./src/App/docker_image_config.xml');
        unlink('./src/App/Script.php');
    }

    private static function toCamelCase($string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(preg_replace('/^a-z0-9' . implode('', []) . ']+/', ' ', $string))));
    }

    private static function toUnderscore($string): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }

    private static function addPHPStormConfig($answers): void
    {
        if (!file_exists('./.idea/runConfigurations')) {
            mkdir('./.idea/runConfigurations', 0777, true);
        }

        $content = file_get_contents('./src/App/docker_image_config.xml');
        $content = str_replace('AWS_ACCESS_KEY_ID', $answers['Access_Key_ID'], $content);
        $content = str_replace('AWS_SECRET_ACCESS_KEY', $answers['Secret_Access_Key'], $content);
        file_put_contents('./src/App/docker_image_config.xml', $content);

        copy('./src/App/docker_image_config.xml', './.idea/runConfigurations/docker_image_config.xml');
    }
}
