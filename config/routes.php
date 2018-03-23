<?php
/** @var \Zend\Expressive\Application $app */

$app->get('/', App\Action\HomeAction::class, 'home');
$app->get('/env-vars', App\Action\EnvVarsAction::class, 'envVars');

$app->post('/safe-box/encrypt', App\Action\SafeBoxAction::class, 'safeBoxEncrypt');
$app->post('/safe-box/decrypt', App\Action\SafeBoxAction::class, 'safeBoxDecrypt');

$app->post('/safe-box', App\Action\SafeBoxAction::class, 'safeBoxCreate');
$app->get('/safe-box[/{name}]', App\Action\SafeBoxAction::class, 'safeBoxRead');
$app->patch('/safe-box/{name}', App\Action\SafeBoxAction::class, 'safeBoxUpdate');
$app->delete('/safe-box/{name}', App\Action\SafeBoxAction::class, 'safeBoxDelete');
