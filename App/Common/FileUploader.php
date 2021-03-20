<?php

//文件上传
namespace App\Common;

class FileUploader
{
    //设定属性：保存允许上传的MIME类型
    private static $types = array( 'image/jpg', 'image/jpeg', 'image/pjpeg', 'application/vnd.ms-excel' );

    //修改允许上传类型
    public static function setTypes($types = array())
    {
        //判定是否为空
        if (!empty($types)) self::$types = $types;
    }

    public static $error;    //记录单文件上传过程中出现的错误信息
    public static $errors;   //记录多文件上传过程中出现的错误信息
    public static $files;    //记录多文件上传成功后文件名对应信息
    public static $path = APP_FULL_PATH . '/Cache/upload/';

    public static function creatdir($path)
    {
        $path = $path === '' ? self::$path : $path;
        if (!is_dir($path)) {
            if (static::creatdir(dirname($path))) {
                mkdir($path, 0777);
                return $path;
            }
        } else {
            return $path;
        }

    }

    public static function delFiles($path, $del_all = false)
    {
        if (is_dir($path)) {
            //扫描一个目录内的所有目录和文件并返回数组
            $dirs = scandir($path);

            foreach ($dirs as $dir) {
                //排除目录中的当前目录(.)和上一级目录(..)
                if ($dir != '.' && $dir != '..') {
                    //如果是目录则递归子目录，继续操作
                    $sonDir = $path . '/' . $dir;
                    if (is_dir($sonDir)) {
                        //递归删除
                        static::delFiles($sonDir);
                        //目录内的子目录和文件删除后删除空目录
                        @rmdir($sonDir);
                    } else {
                        //如果是文件直接删除
                        $url = iconv('utf-8', 'gbk', $path);
                        if (PATH_SEPARATOR == ':') { //linux
                            unlink($path);
                        } else {  //Windows
                            unlink($url);
                        }
                    }
                }
            }
            @rmdir($path);
        } else {

            $url = iconv('utf-8', 'gbk', $path);
            if (PATH_SEPARATOR == ':') { //linux
                unlink($path);
            } else {  //Windows
                unlink($url);
            }

        }
    }

    /**
     * @desc 单文件上传
     * @param string $path ,上传路径
     * @param string $file ,上传文件信息数组
     * @param int $max = 2M,最大上传大小
     * @return bool|string,成功返回文件名，失败返回false
     */
    public static function uploadOne($path = '', $file = 'file', $max = 2000000)
    {
        $path = is_dir($path) ? $path : self::creatdir($path);
        $file = $_FILES[$file];

        //判定文件有效性
        if (!isset($file['error']) || count($file) != 5) {
            self::$error = '错误的上传文件！';
            return false;
        }
        //路径判定
        if (!is_dir($path)) {
            self::$error = '存储路径不存在！';
            return false;
        }
        //判定文件是否正确上传
        switch ($file['error']) {
            case 1:
            case 2:
                self::$error = '文件超过服务器允许大小！';
                return false;
            case 3:
                self::$error = '文件只有部分被上传！';
                return false;
            case 4:
                self::$error = '没有选中要上传的文件！';
                return false;
            case 6:
            case 7:
                self::$error = '服务器错误！';
                return false;
        }
        //判定文件类型
        if (!in_array($file['type'], self::$types)) {
            self::$error = '当前上传的文件类型不允许！';
            return false;
        }
        //判定业务大小
        if ($file['size'] > $max) {
            self::$error = '当前上传的文件超过允许的大小！当前允许的大小是：' . (string)($max / 1000000) . 'M';
            return false;
        }
        //获取随机名字
        $filename = self::getRandomName($file['name']);

        //移动上传的临时文件到指定目录
        if (move_uploaded_file($file['tmp_name'], $path . '/' . $filename)) {
            //成功
            return $path . $filename;
        } else {
            //失败
            self::$error = '文件移动失败！';
            return false;
        }
    }

    /**
     * @desc 多文件上传
     * @param string $path ,上传路径
     * @param array $files ,上传文件信息二维数组
     * @param int $max = 2M,最大上传大小
     * @return bool 是否全部上传成功
     */
    public static function uploadAll($path, $files = 'name', $max = 2000000)
    {
        is_dir($path) ?: self::creatdir($path);
        $files = $_FILES[$files];

        for ($i = 0, $len = count($files['name']); $i < $len; $i++) {
            $file = array(
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i]
            );
            $res = self::uploadOne($file, $path, $max);
            if (!$res) {
                //错误处理
                $error = self::$error;
                self::$errors[] = "文件：{$file['name']}上传失败:{$error}!<br>";
            } else {
                self::$files[] = $file['name'] . '=>' . $res;
            }
        }
        if (!empty(self::$errors)) {
            //错误处理
            //var_dump(self::$errors);
            return false;
        } else {
            return true;
        }
    }

    /**
     * @desc 获取随机文件名
     * @param string $filename ,文件原名
     * @param string $prefix ,前缀
     * @return string,返回新文件名
     */
    public static function getRandomName($filename, $prefix = 'image')
    {
        //取出源文件后缀
        $ext = strrchr($filename, '.');
        //构建新名字
        $new_name = $prefix . date('YmdHis');
        //增加随机字符（6位大写字母）
        for ($i = 0; $i < 6; $i++) {
            $new_name .= chr(mt_rand(65, 90));
        }
        //返回最终结果
        return $new_name . $ext;
    }

    public static function UploadTpl($title = '文件上传', $url = '/upload.php')
    {
        return '
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>' . $title . '</title>
</head>
<body>
' . $title . '
<form action="' . $url . '"  method="post" enctype="multipart/form-data">
<input type="file" name="file"><br>
<input type="submit"><br>
</form>
</body>
</html>
        ';
    }

    public static function UploadAllTpl()
    {
        return '
        <!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>文件上传</title>
</head>
<body>
多文件上传表单
<form action="uploadall.php" method="post" enctype="multipart/form-data">
    <input name="file[]" type="file"><br>
    <input name="file[]" type="file"><br>
    <input name="file[]" type="file"><br>
    <input type="submit"/><br>
</form> 
</body>
</html>
        ';
    }
}