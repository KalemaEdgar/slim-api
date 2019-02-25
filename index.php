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

// Retrieve all the messages
$app->get('/messages', function ($request, $response, $args) {
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

// Create a new message
$app->post('/messages', function ($request, $response, $args) {
    // Retrieve the message parameter. If it doesnot exist, then the default is an empty string as below
    $_message = $request->getParsedBodyParam('message', '');

    $message = new Message;
    $message->body = $_message;
    $message->user_id = -1; // Retrieve the user based on the Bearer Token sent with the request
    $message->save();

    if ($message->id) {
        $payload = [
            'message_id' => $message->id,
            'message_uri' => '/messages/' . $message->id
        ];
        return $response->withStatus(201)->withJson($payload);
    } else {
        return $response->withStatus(400);
    }

});

// Create a message with an uploaded file
$app->post('/addMessageWithFileUpload', function ($request, $response, $args) { 

    $_message = $request->getParsedBodyParam('message', '');
    
    $imagePath = '';
    $files = $request->getUploadedFiles();
    $newFile = $files['file'];

    if ($newFile->getError() === UPLOAD_ERR_OK) {
        $uploadFileName = $newFile->getClientFileName();
        $newFile->moveTo('assets/images/' . $uploadFileName);
        $imagePath = 'assets/images/' . $uploadFileName;
    } else {
        // Do something if the file upload fails
    }

    $message = new Message;
    $message->body = $_message;
    $message->user_id = -1;
    $message->image_url = $imagePath;
    $message->save();

    if ($message->id) {
        
        $payload = [
            'message_id' => $message->id,
            'message_uri' => '/messages/' . $message->id
        ];

        return $response->withStatus(201)->withJson($payload);

    } else {
        
        return $response->withStatus(400);

    }

});

// Delete a message
$app->delete('/messages/{message_id}', function ($request, $response, $args) {
    // Lookup the message
    $message = Message::find($args['message_id']);
    $message->delete();

    // Check if the message still exists
    if ($message->exists) {
        return $response->withStatus(400);
    } else {
        return $response->withStatus(204);
    }

});

$app->run();
