<?php
namespace App;

define('APP_DEBUG', TRUE);
require './SinglePHP.php';                //采用单独文件部署方式

use SinglePHP\Config;
use SinglePHP\SinglePHP;

SinglePHP::getInstance(Config::Init())->run();     //跑起来啦