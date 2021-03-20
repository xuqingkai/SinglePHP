<?php
namespace App\Controller;

use App\Common\csv;
use App\Common\FileUploader;
use SinglePHP\BaseController;
use SinglePHP\DB;
use SinglePHP\RestfulController;

use function SinglePHP\is_post;

class Test extends RestfulController {   //控制器必须继承Controller类或其子类
    public function Get(){               //默认Action
       $data = [1,2,3,4,5,9];
       $token = $this->getToken($data);
       dump($token);
       $data=  $this->verifyToken($token);
       dump($data);

    }
}


