<?php

require 'vendor/autoload.php';
include 'bootstrap.php';

use Chatter\Models\Message;
use Chatter\Middleware\Logging as ChatterLogging;
use Chatter\Middleware\Authentication as ChatterAuth;

$app = new \Slim\App();
$app->add(new ChatterAuth()); // When the authentication fails (incorrect token), this is throwing PHP errors, handle them
$app->add(new ChatterLogging());

$app->get('/hello/{name}', function ($request, $response, $args) {
    return $response->write('Welcome ' . $args['name'] . ', to your SlimApp');
});

$app->get('/messages', function($request, $response, $args) {
    // return 'These are the application messages';
    $_message = new Message();
    $messages = $_message->all();

    $payload = [];
    foreach ($messages as $message) :
        $payload[$message->id] = [
            'body' => $message->body,
            'user_id' => $message->user_id,
            'created_at' => $message->created_at
        ];
    endforeach;

    return $response->withStatus(200)->withJson($payload);

});

$app->run();
