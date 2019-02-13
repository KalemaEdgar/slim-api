<?php

require 'vendor/autoload.php';
include 'bootstrap.php';

use Chatter\Models\Message;
use Chatter\Middleware\Logging as ChatterLogging;
use Chatter\Middleware\Authentication as ChatterAuth;
use Chatter\Middleware\FileFilter;
use Chatter\Middleware\FileMove;
use Chatter\Middleware\ImageRemoveExif;

$app = new \Slim\App();
$app->add(new ChatterAuth());
$app->add(new ChatterLogging());

$app->group('/messages', function () {
    $this->map(['GET'], '', function ($request, $response, $args) {
        $_message = new Message();

        $messages = $_message->all();

        $payload = [];
        foreach($messages as $_msg) {
            $payload[$_msg->id] = $_msg->output();
        }

        return $response->withStatus(200)->withJson($payload);
    })->setName('get_messages');

    $filter = new FileFilter();
    $removeExif = new ImageRemoveExif();
    $move   = new FileMove();
});

// Run app
$app->run();