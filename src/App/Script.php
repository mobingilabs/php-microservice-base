<?php

declare(strict_types=1);

namespace App;

class Script
{
    const FILES_TO_RENAME = [
        './src/App/Factory/ExampleFactory.php'
    ];

    public static function install(): void
    {
//        $serviceName = self::ask('What is the "micro-service" name?');
        $resourceName = self::ask('What is the "resource" name?');
//        $answer       = self::ask("Service name is [{$serviceName}] and resource name is [{$resourceName}], do you want to continue? y/n");
//
//        if ($answer === 'n' || $answer === 'no') {
//            echo PHP_EOL . 'Canceling...' . PHP_EOL;
//            exit(0);
//        }

        self::renameFiles($resourceName);
        self::replaceFilesContent();
        self::finishScript();
        exit(0);
    }

    /**
     * @param string $question
     * @return string
     */
    private static function ask(string $question): string
    {
        do {
            self::log($question, '33');
            $answer = strtolower(trim(fgets(STDIN)));
        } while (empty($answer));

        return $answer;
    }

    /**
     * @param string $message
     * @param string $color
     * @param string $color
     */
    private static function log(string $message, $color = '0'): void
    {
        // Colors: (0 = White) (31 = Red) (32 = Green) (33 = Yellow) (34 = Blue) (35 = Purple) (36 = Light blue)
        $border = "\033[32m:::\033[0m";
        fwrite(STDOUT, "{$border} \033[{$color}m {$message} \033[0m {$border}" . PHP_EOL);
    }

    private static function renameFiles($resourceName)
    {
        foreach (self::FILES_TO_RENAME as $file) {
            rename($file, str_replace('Example', $resourceName, $file));
        }
    }

    private static function replaceFilesContent()
    {
    }

    private static function finishScript()
    {
    }
}
