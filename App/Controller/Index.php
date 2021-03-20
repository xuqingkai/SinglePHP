<?php
namespace App\Controller;

use App\Common\csv;
use App\Common\FileUploader;
use SinglePHP\BaseController;
use SinglePHP\Cache;
use SinglePHP\Config;
use SinglePHP\Log;
use SinglePHP\Plugin;

use function SinglePHP\is_post;

class Index extends BaseController {
    public function Index(){
        // $this->display();
        // Cache::set('Controller','Index');
        // dump(Cache::get('Controller'));
        // // Log::notice('in plugins');
        // dump(Config::get('LOG_LEVEL'));
        // dump(Config::set('Controller','Index'));
        // dump(Config::all());
        // Plugin::trigger('demo');
    }
    function getSubDirs($dir) 
    {
        $subdirs = array();
        if(!$dh = opendir($dir)) 
            return $subdirs;
        $i = 0;
        while ($f = readdir($dh)) 
        {
             if($f =='.' || $f =='..') 
                continue;
            //如果只要子目录名, path = $f;
            $subdirs[$i] =  $f;
            $i++;
        }
        return $subdirs;
    }
    public function Upload()
    {
         if(is_post()){
          if($file =  FileUploader::uploadOne()){
                $data = csv::import($file);
                FileUploader::delFiles($file);
    
                foreach ($data as $key => $value) {
                    // DB::getInstance();
                }
          }
        }else{
            echo FileUploader::UploadTpl('文件上传','/index.php/index/upload');
        }
    }
}