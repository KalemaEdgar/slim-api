<?php

include 'config/credentials.php'; // Database connections
include 'vendor/autoload.php'; // To pull in Illuminate

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule();

// Create the connection to the database that Eloquent will use
$capsule->addConnection([
    "driver" => "mysql",
    "host" => $database_host,
    "database" => $database_name,
    "username" => $database_user,
    "password" => $database_password,
    "charset" => "utf8",
    "collation" => "utf8_general_ci",
    "prefix" => ""
]);

// Startup Eloquent after setting up the connection
$capsule->bootEloquent();