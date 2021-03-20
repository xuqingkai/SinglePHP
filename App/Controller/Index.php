<?php

namespace App\Controller;

use SinglePHP\BaseController;
use SinglePHP\Cache;
use SinglePHP\Config;
use SinglePHP\Log;
use SinglePHP\Plugin;

class Index extends BaseController
{
    public function Index()
    {
        $this->assign('title',"这是SinglePHP");
        $this->display();

    }
    public function test(){
        Cache::set('Controller','Index');
        dump(Cache::get('Controller'));
        Log::notice('in plugins');
        dump(Config::get('LOG_LEVEL'));
        dump(Config::set('Controller','Index'));
        dump(Config::all());
        Plugin::trigger('demo');
    }
}