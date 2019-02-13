<?php

namespace Chatter\Middleware;

use Chatter\Models\User;

class Authentication
{
    public function __invoke($request, $response, $next)
    {
        // Pick the headers and retrieve the apikey (Authorization: Bearer 123456Token)
        $auth = $request->getHeader('Authorization');
        $_apikey = $auth[0];
        $apikey = substr($_apikey, strpos($_apikey, ' ') + 1);
        // die(var_dump($apikey));

        $user = new User();
        if ( ! $user->authenticate($apikey)) 
        {
            $response->withStatus(401);
            die(var_dump($response));
            return $response;
        }

        $response = $next($request, $response);

        return $response;
    }

}
