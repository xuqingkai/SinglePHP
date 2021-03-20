<?php

namespace App\Common;

class csv
{
    public static $csv_array; //csv数组数据
    public static $csv_column_titles = []; //csv数据标题
    public static $csv_str;  //csv文件数据
    public static $path;
    public static $column;
    public static $msg;


    /**
     * 导出
     * */
    public static function export()
    {
        if (empty(static::$csv_array) || empty(static::$column)) {
            return false;
        }
        $param_arr = static::$csv_array;

        $export_str = implode(',', $param_arr['nav']) . "\n";
        unset($param_arr['nav']);
        //组装数据
        foreach ($param_arr as $k => $v) {
            foreach ($v as $k1 => $v1) {
                $export_str .= implode(',', $v1) . "\n";
            }
        }
        //将$export_str导出
        header("Cache-Control: public");
        header("Pragma: public");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=txxx.csv");
        header('Content-Type:APPLICATION/OCTET-STREAM');
        ob_start();
        //  $file_str=  iconv("utf-8",'gbk',$export_str);
        ob_end_clean();
        echo $export_str;
        die;
    }

    /**
     * 导入
     * */
    public static function import($path, $start_row = 2, $start_column = 0)
    {
        $flag = false;
        $code = 0;
        static::$msg = '未处理';
        $filesize = 1; //1MB
        $maxsize = $filesize * 1024 * 1024;
        $max_column = 1000;

        //检测文件是否存在
        if ($flag === false) {
            if (!file_exists($path)) {
                static::$msg = '文件不存在';
                $flag = true;
            }
        }
        //检测文件格式
        if ($flag === false) {
            $ext = pathinfo($path)['extension'];
            if ($ext != 'csv') {
                static::$msg = '只能导入CSV格式文件';
                $flag = true;
            }
        }

        //检测文件大小
        if ($flag === false) {
            if (filesize($path) > $maxsize) {
                static::$msg = '导入的文件不得超过' . $maxsize . 'B文件';
                $flag = true;
            }
        }

        //读取文件
        if ($flag == false) {
            $row = 1;
            if (!$handle = fopen($path, 'r')) {
                exit("打开文件失败");
            }
            $dataArray = array();
            while ($data = fgetcsv($handle, $max_column, ",")) {
                $data = static::detect_encoding($data);
                $num = count($data);

                if ($flag === false) {
                    for ($i = 0; $i < $num; $i++) {
                        if ($row < $start_row) {
                            static::$csv_column_titles[$row][$i] = $data[$i];

                        } else {
                            //组建数据
                            $dataArray[$row][$i] = $data[$i];
                        }
                    }
                }
                $row++;
            }
        }

        return $dataArray;
    }

    /**
     * @ string 需要转换的文字
     * @ encoding 目标编码
     **/
    static function detect_encoding($string, $encoding = 'utf8')
    {
        $is_utf8 = preg_match('%^(?:[\x09\x0A\x0D\x20-\x7E]| [\xC2-\xDF][\x80-\xBF]| \xE0[\xA0-\xBF][\x80-\xBF] | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  | \xED[\x80-\x9F][\x80-\xBF] | \xF0[\x90-\xBF][\x80-\xBF]{2} | [\xF1-\xF3][\x80-\xBF]{3} | \xF4[\x80-\x8F][\x80-\xBF]{2} )*$%xs', $string);
        if ($is_utf8 && $encoding == 'utf8') {
            return $string;
        } elseif ($is_utf8) {
            return mb_convert_encoding($string, $encoding, "UTF-8");
        } else {
            return mb_convert_encoding($string, $encoding, 'gbk,gb2312,big5');
        }
    }
}