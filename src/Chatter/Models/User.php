<?php

namespace Chatter\Models;

class User extends \Illuminate\Database\Eloquent\Model
{
    public function authenticate($apikey) 
    {
        // Change this take() method to act like laravel's FindOrFail so that the request fails gracefully when the apikey is not found
        $user = User::where('apikey', '=', $apikey)->take(1)->get(); 
        // die(var_dump($user));
        // Add a check here to fail if the user is not found
        $this->details = $user[0];

        return ($user[0]->exists) ? true : false;
    }
}