<?php

declare(strict_types=1);

namespace App;

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
        './src/App/Validation/ExampleCreateSchema.json',
        './src/App/Validation/ExampleUpdateSchema.json',
        './README.md',
    ];

    const FILES_TO_RENAME = [
        './src/App/Factory/ExampleFactory.php',
        './src/App/Handler/ExampleHandler.php',
        './src/App/Model/ExampleModel.php',
        './src/App/Validation/ExampleCreateSchema.json',
        './src/App/Validation/ExampleUpdateSchema.json',
    ];

    public static function install(): void
    {
        $serviceName  = self::ask('What is the "micro-service" name?');
        $resourceName = self::ask('What is the "resource" name?');
        $answer       = self::ask("Service name is [{$serviceName}] and resource name is [{$resourceName}], do you want to continue? y/n");

        if ($answer === 'n' || $answer === 'no') {
            echo PHP_EOL . 'Canceling...' . PHP_EOL;
            exit(0);
        }

        self::replaceFilesContentResource($resourceName);
        self::replaceFilesContentService($serviceName);
        self::renameFiles($resourceName);
        self::finishScript();
    }

    private static function ask(string $question): string
    {
        do {
            self::log($question, '33');
            $answer = trim(fgets(STDIN));
        } while (empty($answer));

        return $answer;
    }

    private static function log(string $message, $color = '0'): void
    {
        // Colors: (0 = White) (31 = Red) (32 = Green) (33 = Yellow) (34 = Blue) (35 = Purple) (36 = Light blue)
        $border = "\033[32m:::\033[0m";
        fwrite(STDOUT, "{$border} \033[{$color}m {$message} \033[0m {$border}" . PHP_EOL);
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
$ composer install
```
Follow the composer instructions and it will generate the project using data provided in the wizard.

';

        $readme = file_get_contents('./README.md');
        $readme = str_replace($search, '', $readme);
        file_put_contents('./README.md', $readme);

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
}
