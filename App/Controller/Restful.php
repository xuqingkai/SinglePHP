<?php

namespace App\Controller;

use SinglePHP\RestfulController;

class Restful extends RestfulController
{
    public function Get()
    {               //默认Action
        $data = [ 1, 2, 3, 4, 5, 9 ];
        $token = $this->getToken($data);
        return $this->json($token);
    }

    public function Post(){
        $data = [ 1, 2, 3, 4, 5, 9 ];
        $token = $this->getToken($data);
        $data = $this->verifyToken($token);
        return $this->json($data);

    }
}


