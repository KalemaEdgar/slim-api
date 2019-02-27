<?php

require 'vendor/autoload.php';
include 'bootstrap.php';

use Chatter\Models\Message;
use Chatter\Middleware\Logging as ChatterLogging;
use Chatter\Middleware\Authentication as ChatterAuth;
use Chatter\Middleware\FileFilter;
use Chatter\Middleware\ImageRemoveExif;
use Chatter\Middleware\FileMove;


$app = new \Slim\App();
// Register the middleware. 
// The middleware registered with ($app->add()) is added to the entire app and runs for all routes (Authentication, Logging)
// You can specify the middleware to run only for a specific route by attaching it only to that route and not to $app
$app->add(new ChatterAuth()); // When authentication fails (incorrect token), it is throwing PHP errors, handle them
$app->add(new ChatterLogging());

/**
 * Versioning.
 * Easiest way to version in Slim is by using groups
 */
// Access this one as http://localhost/slimapp/v1/messages
$app->group('/v1', function () {
    $this->group('/messages', function () {
        $this->map(['GET'], '', function ($request, $response, $args) {
            $_message = new Message();
            $messages = $_message->all();

            $payload = [];
            foreach ($messages as $message) {
                // Use the Message model functionality to retrieve the payload
                $payload[$message->id] = $message->output();
            }

            return $response->withStatus(200)->withJson($payload);
        })->setName('get_messages');
    });
});

$app->group('/v2', function () {
    $this->group('/messages', function () {
        $this->map(['GET'], '', function ($request, $response, $args) {
            $_message = new Message();
            $messages = $_message->all();

            $payload = [];
            foreach ($messages as $message) {
                // Use the Message model functionality to retrieve the payload
                $payload[$message->id] = $message->output();
            }

            return $response->withStatus(200)->withJson($payload);
        })->setName('get_messages_v2');
    });
});

/**
 * Refactoring the API
 * Using groups and moving the implementation to the model.
 * Can have more than one route in a group like a delete, post etc
 * 
 * Refactor the API to include the DELETE and POST variables to use a Model like output()
 */
$app->group('/messages', function () {
    $this->map(['GET'], '', function ($request, $response, $args) {
        $_message = new Message();
        $messages = $_message->all();

        $payload = [];
        foreach ($messages as $message) {
            // Use the Message model functionality to retrieve the payload
            $payload[$message->id] = $message->output();
        }

        return $response->withStatus(200)->withJson($payload);
    })->setName('get_messages');
});

// Retrieve all the messages
$app->get('/messagesBeforeRefactoring', function ($request, $response, $args) {
    // return 'These are the application messages';
    $_message = new Message();
    $messages = $_message->all();

    $payload = [];
    foreach ($messages as $message) :
        $payload[$message->id] = [
            'body' => $message->body,
            'user_id' => $message->user_id,
            'user_uri' => '/user/' . $message->user_id,
            'created_at' => $message->created_at,
            'image_url' => $message->image_url,
            'message_id' => $message->id,
            'message_uri' => '/messages/' . $message->id

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
            'message_uri' => '/messages/' . $message->id,
            'image_url' => $message->image_url
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

// Create a message while securely uploading a file to Amazon S3
$filter = new FileFilter(); // Filter for only jpeg and png files
$removeExif = new ImageRemoveExif(); // remove dangerous data
$move = new FileMove(); // Move/Store the image on Amazon S3 cloud
$app->post('/addMessageWithFileUploadAndSecure', function ($request, $response, $args) {

    $_message = $request->getParsedBodyParam('message', '');

    $imagePath = '';
    $message = new Message;
    $message->body = $_message;
    $message->user_id = -1;
    $message->image_url = $request->getAttribute('png_filename');
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
})->add($filter)->add($removeExif)->add($move);

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

$app->get('/hello/{name}', function ($request, $response, $args) {
    return $response->write('Welcome ' . $args['name'] . ', to your SlimApp');
});

$app->run();
