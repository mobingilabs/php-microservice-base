<?php

declare(strict_types=1);

namespace App;

use Composer\Composer;
use Composer\IO\IOInterface;
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

    /** @var IOInterface */
    private $io;

    /** @var Composer */
    private $composer;

    /** @var array */
    private $answers;

    /**
     * @param Event $event
     * @throws \Exception
     */
    public static function install(Event $event): void
    {
        $installer = new self($event->getIO(), $event->getComposer());

        $installer->answers['Service_Name'] = $installer->io->ask("\n  <question>>>> What is the 'micro-service' name?</question> [default: <comment>my service</comment>] ", 'my service');
//        $installer->answers['Resource_Name']   = $installer->io->ask("\n  <question>>>> What is the 'resource' name?</question> [default: <comment>my resource</comment>] ", 'my resource');
//        $installer->answers['PHPStorm_Config'] = $installer->io->askConfirmation("\n  <question>>>> Are you using PHPStorm? Do you want to auto add Docker image config?</question> (y/n) [default: <comment>yes</comment>] ", true);
//        if ($installer->answers['PHPStorm_Config']) {
//            $installer->io->write("\n  <info>>>> Please provide your AWS credentials</info>");
//            $installer->answers['Access_Key_ID']     = $installer->io->ask("\n  <question>>>> Access Key ID:</question> ", 'not-provided');
//            $installer->answers['Secret_Access_Key'] = $installer->io->ask("\n  <question>>>> Secret Access Key:</question> ", 'not-provided');
//            $installer->answers['PHPStorm_Config']   = 'yes';
//        } else {
//            unset($installer->answers['PHPStorm_Config']);
//        }

        $installer->answers['Github_Config'] = $installer->io->askConfirmation("\n  <question>>>> Do you want to auto add Github repository?</question> (y/n) [default: <comment>yes</comment>] ", true);
        if ($installer->answers['Github_Config']) {
            $installer->io->write("\n  <info>>>> Please provide your Github configurations</info>");
            $installer->answers['Github_Access_Key_Token'] = $installer->io->ask("\n  <question>>>> Access Key Token:</question> ", 'c56c839089358145afde1ab47904ce6f072399b1');
            $installer->answers['Github_Team_ID']          = $installer->io->askAndValidate("\n  <question>>>> Team ID (numbers only):</question> ", function ($value) {
                if (!intval($value)) {
                    throw new \Exception('Team ID should be number only.');
                }

                return $value;
            }, 1, 2833513);

            $installer->answers['Github_Config'] = 'yes';
        } else {
            unset($installer->answers['Github_Config']);
        }

        $installer->io->write("\n  ::: Your current configuration:");
        $installer->io->write("  -------------------------------");
        foreach ($installer->answers as $key => $answer) {
            $installer->io->write("  ::: {$key}: <info>{$answer}</info>");
        }

        $start = $installer->io->askConfirmation("\n  <question>>>> Do you want to proceed?</question> (y/n) [default: <comment>yes</comment>] ", true);

        if (!$start) {
            $installer->io->write("\n  <warning>>>> Canceling...</warning>");
            exit(0);
        }

//        $$installer->replaceFilesContentResource($installer->answers['Resource_Name']);
//        $$installer->replaceFilesContentService($installer->answers['Service_Name']);
//        $$installer->renameFiles($installer->answers['Resource_Name']);

        if (isset($installer->answers['PHPStorm_Config'])) {
            $installer->io->write("\n  <info>>>> Creating PHPStorm Configurations...</info>");
            $$installer->addPHPStormConfig();
        }

        if (isset($installer->answers['Github_Config'])) {
            $installer->io->write("\n  <info>>>> Creating Github Configurations...</info>");
            $installer->createGithubConfig();
        }

        $installer->finishScript();
    }

    public function __construct(IOInterface $io, Composer $composer, string $projectRoot = null)
    {
        $this->io       = $io;
        $this->composer = $composer;
    }

    private function renameFiles($resourceName)
    {
        $resourceName = $this->toCamelCase($resourceName);
        $resourceName = ucfirst($resourceName);
        foreach (self::FILES_TO_RENAME as $file) {
            rename($file, str_replace('Example', $resourceName, $file));
        }
    }

    private function replaceFilesContentResource($resourceName)
    {
        $resourceName   = $this->toCamelCase($resourceName);
        $ucFirstName    = ucfirst($resourceName);
        $underscoreName = $this->toUnderscore($resourceName);
        foreach (self::FILES_TO_REPLACE_RESOURCE as $file) {
            $content = file_get_contents($file);
            $content = str_replace('Example', $ucFirstName, $content);
            $content = str_replace('example_id', "{$underscoreName}_id", $content);
            $content = str_replace('/example', '/' . str_replace('_', '-', $underscoreName), $content);
            $content = str_replace('example', $resourceName, $content);
            file_put_contents($file, $content);
        }
    }

    private function replaceFilesContentService($serviceName)
    {
        $serviceName = $this->toCamelCase($serviceName);
        $serviceName = $this->toUnderscore($serviceName);
        $serviceName = str_replace('_', '-', $serviceName);
        foreach (self::FILES_TO_REPLACE_SERVICE as $file) {
            $content = file_get_contents($file);
            $content = str_replace('SERVICE_NAME', $serviceName, $content);
            file_put_contents($file, $content);
        }
    }

    private function finishScript()
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

    private function toCamelCase($string): string
    {
        return lcfirst(str_replace(' ', '', ucwords(preg_replace('/^a-z0-9' . implode('', []) . ']+/', ' ', $string))));
    }

    private function toUnderscore($string): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }

    private function addPHPStormConfig(): void
    {
        if (!file_exists('./.idea/runConfigurations')) {
            mkdir('./.idea/runConfigurations', 0777, true);
        }

        $content = file_get_contents('./src/App/docker_image_config.xml');
        $content = str_replace('AWS_ACCESS_KEY_ID', $this->answers['Access_Key_ID'], $content);
        $content = str_replace('AWS_SECRET_ACCESS_KEY', $this->answers['Secret_Access_Key'], $content);
        file_put_contents('./src/App/docker_image_config.xml', $content);

        copy('./src/App/docker_image_config.xml', './.idea/runConfigurations/docker_image_config.xml');
    }

    /**
     * @throws \Exception
     */
    private function createGithubConfig()
    {
//        $this->createRepository();
        $this->execGitCommands();

        $this->io->write("\n  ::: Your current configuration:");
        $this->io->write("  -------------------------------");
        foreach ($this->answers as $key => $answer) {
            $this->io->write("  ::: {$key}: <info>{$answer}</info>");
        }

        exit;
    }

    private function execGitCommands()
    {

        exec('git init', $output, $return);
        exec('git config --local user.name "mobingideployer"', $output, $return);
        exec('git config --local user.email "dev@mobingi.com"', $output, $return);
        exec('git add .', $output, $return);
        exec('git commit -m "first commit"', $output, $return);
        exec('git remote add origin ' . $this->answers['Github_SSH_Url'], $output, $return);
        exec('git push -u origin master', $output, $return);
    }

    /**
     * @throws \Exception
     */
    private function createRepository()
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://api.github.com/orgs/mobingilabs/repos?access_token={$this->answers['Github_Access_Key_Token']}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_USERAGENT      => 'php-curl',
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => "{\n\t\"name\": \"{$this->answers['Service_Name']}\",\n\t\"description\": \"Microservice {$this->answers['Service_Name']}.\",\n\t\"homepage\": null,\n\t\"private\": true,\n\t\"has_issues\": true,\n\t\"has_projects\": true,\n\t\"has_wiki\": true,\n\t\"team_id\": {$this->answers['Github_Team_ID']},\n\t\"auto_init\": false,\n\t\"allow_squash_merge\": true,\n    \"allow_merge_commit\": true,\n    \"allow_rebase_merge\": true\n}",
            CURLOPT_HTTPHEADER     => array(
                "Cache-Control: no-cache",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err      = curl_error($curl);

        curl_close($curl);

        if ($err) {
            throw new \Exception("cURL Error #: {$err}");
        } else {
            echo $response;
            $data = json_decode($response);

            $this->answers['Github_SSH_Url'] = $data->ssh_url;
        }
    }
}
