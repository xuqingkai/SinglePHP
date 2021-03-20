<?php

namespace App\Controller;

use SinglePHP\ApiController;

class Api extends ApiController
{

    public function get_token()
    {               //默认Action
        $data = [ 1, 2, 3, 4, 5, 9 ];
        $token = $this->getToken($data);
        return $this->json($token);
    }

    public function get_data(){
        $data = [ 1, 2, 3, 4, 5, 9 ];
        $token = $this->getToken($data);
        $data = $this->verifyToken($token);
        return $this->json($data);

    }
}


