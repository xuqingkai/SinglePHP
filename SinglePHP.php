<?php

/**
 * 打印函数
 * 浏览器友好的变量输出
 * @param mixed $var 变量
 * @param boolean $echo 是否输出 默认为True 如果为false 则返回输出字符串
 * @param string $label 标签 默认为空
 * @param boolean $strict 是否严谨 默认为true
 * @return void|string
 */
function dump($var, $echo = true, $label = null, $strict = true)
{
    if (PHP_SAPI == 'cli') {
        print_r($var) ;echo ''. PHP_EOL;
    } else {
        $label = ($label === null) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace("/\]\=\>\n(\s+)/m", '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo($output);
            return null;
        } else
            return $output;
    }
}
namespace SinglePHP;
/**
 * SinglePHP-Exp 单php文件精简框架。
 * @author dragonlhp
 * @version 2021-03-20
 */

/**
 * 自动创建多级目录
 */
function creatdir($path)
{
    if (!is_dir($path)) {
        if (creatdir(dirname($path))) {
            mkdir($path, 0777);
            return true;
        }
    } else {
        return true;
    }
}

/**
 * 判断是否是Post请求
 * @return bool
 */
function is_post()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * 按配置生成url
 * @param string $ModuleAction
 * @param array $param
 * @return string
 */
function Url($ModuleAction, $param = [])
{
    if (strcasecmp(Config::get('PATH_MODE'), 'NORMAL') === 0) {
        $pathInfoArr = explode('/', trim($ModuleAction, '/'));
        $moduleName = valempty($pathInfoArr, 0, 'Index');
        $actionName = valempty($pathInfoArr, 1, 'Index');
        $url = 'c=' . $moduleName . '&a=' . $actionName;
        if (is_string($param))
            $url .= '&' . $param; else {
            foreach ($param as $k => $v)
                $url .= '&' . $k . '=' . urlencode($v);
        }
        return $_SERVER['SCRIPT_NAME'] . '?' . $url;
    } else {
        // pathinfo
        $url = trim($ModuleAction, '/');
        if (is_string($param))
            $url .= '/' . strtr($param, '&=', '//'); else {
            foreach ($param as $k => $v)
                $url .= '/' . $k . '/' . urlencode($v);
        }
        return APP_URL . '/' . $url . '.' . ltrim(Config::get("URL_HTML_SUFFIX"), '.');
    }
}

/**
 * 终止程序运行
 * @param string|array|Error $err 终止原因 or Error Array
 */
function Halt($err)
{
    $e = [];
    if (is_array($err))
        $e = $err; elseif (is_string($err))
        $e['message'] = $err;
    else {
        $e['message'] = $err->getMessage();
        $e['file'] = $err->getFile();
        $e['line'] = $err->getLine();
        $e['trace'] = $err->getTraceAsString();
    }
    Log::fatal($e['message'] . ' debug_trace:' . $e['trace']);
    if (IS_CLI) exit ($e['message'] . ' File: ' . $e['file'] . '(' . $e['line'] . ') ' . $e['trace']);
    if (!APP_DEBUG) $e = $e['message'];
    header("Content-Type: text/html; charset=utf-8");
    exit (var_dump($e));
    // . '<pre>' . '</pre>';
}

/**
 * 获取数据库实例。多数据库可仿照建立
 * @return DB
 * @throws \Exception
 */
function db()
{
    $dbConf = Config::get(array( 'DB_DSN', 'DB_USER', 'DB_PWD', 'DB_OPTIONS', 'TBL_PREFIX' ));
    return DB::getInstance($dbConf);
}

/**
 * 如果文件存在就include进来
 * @param string $file 文件路径
 */
function includeIfExist($file)
{
    if (file_exists($file))
        include_once $file;
}

function sp_output($data, $type)
{
    header('Content-Type: ' . $type . '; charset=utf-8');
    header('Content-Length: ' . strlen($data));
    exit ($data);
}

function sp_tojson($data)
{
    return is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
}

function value($array, $key, $default = null)
{
    return $array != null && isset($array[$key]) ? $array[$key] : $default;
}

function valempty($array, $key, $default = null)
{
    return $array != null && isset($array[$key]) && !empty($array[$key]) ? $array[$key] : $default;
}

function isWin()
{
    return DIRECTORY_SEPARATOR == '\\' ? true : false;
}

define('IS_CLI', PHP_SAPI == 'cli' ? 1 : 0);
defined('APP_DEBUG') || define('APP_DEBUG', false);
if (IS_CLI) defined('ROOT_PATH') || define('ROOT_PATH', getcwd() . DIRECTORY_SEPARATOR);
else defined('ROOT_PATH') || define('ROOT_PATH', dirname(getcwd() . DIRECTORY_SEPARATOR));
defined('APP_FULL_PATH') || define('APP_FULL_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . Config::get('APP_PATH'));
defined('CONTROLLER_SUFFIX') || define('CONTROLLER_SUFFIX', '');
defined('ACTION_SUFFIX') || define('ACTION_SUFFIX', '');
define('APP_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), "/"));
define('IS_AJAX', (strtolower(value($_SERVER, 'HTTP_X_REQUESTED_WITH')) == 'xmlhttprequest') ? true : false);

/**
 * 总控类
 */
class SinglePHP
{
    private static $_instance = null;
    // 单例
    private static $appNamespace = 'App';
    // 应用的主namespace
    private static $ctlNamespace = 'Controller';
    // 控制器的namespace
    protected $config = [];

    /**
     * 构造函数，初始化配置
     * @param array $conf
     */
    private function __construct($conf)
    {
        $this->config = $conf;
        if (isset($conf["APP_NAMESPACE"])) self::$appNamespace = $conf["APP_NAMESPACE"];
        if (isset($conf["CTL_NAMESPACE"])) self::$ctlNamespace = $conf["CTL_NAMESPACE"];
    }

    /**
     * 获取单例
     * @param array $conf
     * @return SinglePHP
     */
    public static function getInstance($conf)
    {
        if (self::$_instance == null)
            self::$_instance = new self($conf);
        return self::$_instance;
    }

    /**
     * 运行应用实例
     * @access public
     * @return void
     */
    public function run()
    {
        register_shutdown_function(array( 'SinglePHP\SinglePHP', 'appFatal' ));
        // 错误和异常处理
        set_error_handler(array( 'SinglePHP\SinglePHP', 'appError' ));
        set_exception_handler(array( 'SinglePHP\SinglePHP', 'appException' ));
        date_default_timezone_set("Asia/Shanghai");
        if (Config::get('USE_SESSION') == true) \session_start();
        includeIfExist(APP_FULL_PATH . '/Functions.php');
        spl_autoload_register([ 'SinglePHP\SinglePHP', 'autoload' ]);
        if (IS_CLI) {
            // 命令行模式
            Config::set('PATH_MODE', 'PATH_INFO');
            $tmp = parse_url($_SERVER['argv'][1]);
            $_SERVER['PATH_INFO'] = $tmp['path'];
            $tmp = explode('&', $tmp['query']);
            foreach ($tmp as $one) {
                list($k, $v) = explode('=', $one);
                $_GET[$k] = $v;
            }
        }
        // 路径解析
        $pathInfo = value($_SERVER, 'PATH_INFO', '');
        if ($pathInfo === '') {
            $moduleName = value($_GET, 'c', 'Index');
            $actionName = value($_GET, 'a', 'Index');
        } else {
            $pathInfo = preg_replace('/\.(' . ltrim(Config::get("URL_HTML_SUFFIX"), '.') . ')$/i', '', $pathInfo);
            $pathInfoArr = explode('/', trim($pathInfo, '/'));
            $moduleName = valempty($pathInfoArr, 0, 'Index');
            $actionName = $this->parseActionName($pathInfoArr);
        }
        $this->callActionMethod($moduleName, $actionName);
    }

    /**
     * 解析Action名及QS参数
     * @param array $pathInfoArr
     * @return string
     */
    protected function parseActionName($pathInfoArr)
    {
        $actionName = valempty($pathInfoArr, 1, 'Index');
        $queryParam = [];
        for ($idx = 2; $idx < count($pathInfoArr); $idx++, $idx++)
            $queryParam[$pathInfoArr[$idx]] = value($pathInfoArr, $idx + 1, '');
        $_GET = array_merge($_GET, $queryParam);
        $_REQUEST = array_merge($_REQUEST, $queryParam);
        return $actionName;
    }

    /**
     * 解析执行模块及方法
     * @param string $moduleName
     * @param string $actionName
     */
    protected function callActionMethod($moduleName, $actionName)
    {
        define("MODULE_NAME", $moduleName);
        $controllerClass = "\\" . self::$appNamespace . "\\" . self::$ctlNamespace . "\\" . MODULE_NAME . CONTROLLER_SUFFIX;
        if (!class_exists($controllerClass) && !preg_match('/^[A-Za-z][\w|\.]*$/', MODULE_NAME))
            Halt('控制器 ' . $controllerClass . ' 不存在');
        $controller = new $controllerClass();
        $isRestful = $controller instanceof RestfulController;
        define("ACTION_NAME", $isRestful ? ucfirst(strtolower($this->httpmethod())) : $actionName);
        // Get Post Put Patch Delete Options
        if (!method_exists($controller, ACTION_NAME . ACTION_SUFFIX))
            Halt('方法 ' . ACTION_NAME . ' 不存在');
        $result = $controller->{ACTION_NAME . ACTION_SUFFIX}();
        // call_user_func(array($controller, ACTION_NAME.'Action'));
        if (IS_CLI) {
            if (is_array($result)) {
                dump($result) . PHP_EOL;
                die;
            }
            exit($result . PHP_EOL);
        } elseif ($result != NULL && $isRestful) {
            sp_output(sp_tojson($result), "application/json");
        }
    }

    /**
     * 获取http的method
     * @return string
     */
    protected function httpmethod()
    {
        if (isset($_POST['_method']))       // 用post方式模拟restful方法
            return $_POST['_method'];
        elseif (isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']))
            return $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        else
            return $_SERVER['REQUEST_METHOD'] ?: 'GET';
    }

    /**
     * 自动加载函数
     * @param string $class 类名
     */
    public static function autoload($class)
    {
        if ($class[0] === self::$appNamespace[0] && strncmp($class, self::$appNamespace . "\\", strlen(self::$appNamespace) + 1) === 0) {
            $classfile = strtr(substr($class, strlen(self::$appNamespace)), "\\", "/");
            includeIfExist(APP_FULL_PATH . $classfile . '.php');
            // 默认Namespace路径和文件路径一致
        }
    }

    // 接受PHP内部回调异常处理
    static function appException($error)
    {
        Halt($error);
    }

    // 自定义错误处理
    static function appError($errno, $errstr, $errfile, $errline)
    {
        $haltArr = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR );
        if (in_array($errno, $haltArr))
            Halt("Errno: $errno $errstr File: $errfile line: $errline.");
    }

    // 致命错误捕获
    static function appFatal()
    {
        $error = error_get_last();
        // last error with keys "type", "message", "file" and "line". Returns &null; if no error.
        $haltArr = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR );
        if ($error && in_array($error['type'], $haltArr))
            Halt($error);
    }
}

/**
 * Json Web Token
 */
trait Jwt
{
    //头部
    /**
     * [
     *  'iss'=>'jwt_admin',  //该JWT的签发者
     *  'iat'=>time(),  //签发时间
     *  'exp'=>time()+7200,  //过期时间
     *  'nbf'=>time()+60,  //该时间之前不接收处理该Token
     *  'sub'=>'www.admin.com',  //面向的用户
     *  'jti'=>md5(uniqid('JWT').time())  //该Token唯一标识
     * ]
     */
    private static $header = [
        'alg' => 'HS256', //生成signature的算法
        'typ' => 'JWT'    //类型
    ];
    //使用HMAC生成信息摘要时所使用的密钥
    private static $key = '123456';

    public function setHeader($header = [])
    {
        self::$header = array_merge(self::$header, $header);
    }

    /**
     * 获取jwt token
     * @param array $payload jwt载荷   格式如下非必须
     * @return bool|string
     */
    public static function getToken($payload)
    {
        if (is_array($payload)) {
            $base64header = self::base64UrlEncode(json_encode(self::$header, JSON_UNESCAPED_UNICODE));
            $base64payload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
            $token = $base64header . '.' . $base64payload . '.' . self::signature($base64header . '.' . $base64payload, self::$key, self::$header['alg']);
            return $token;
        } else {
            return false;
        }
    }

    /**
     * 验证token是否有效,默认验证exp,nbf,iat时间
     * @param string $Token 需要验证的token
     * @return bool|string
     */
    public static function verifyToken($Token)
    {
        $tokens = explode('.', $Token);
        if (count($tokens) != 3)
            return false;
        list($base64header, $base64payload, $sign) = $tokens;
        //获取jwt算法
        $base64decodeheader = json_decode(self::base64UrlDecode($base64header), JSON_OBJECT_AS_ARRAY);
        if (empty($base64decodeheader['alg']))
            return false;
        //签名验证
        if (self::signature($base64header . '.' . $base64payload, self::$key, $base64decodeheader['alg']) !== $sign)
            return false;
        $payload = json_decode(self::base64UrlDecode($base64payload), JSON_OBJECT_AS_ARRAY);
        //签发时间大于当前服务器时间验证失败
        if (isset($payload['iat']) && $payload['iat'] > time())
            return false;
        //过期时间小宇当前服务器时间验证失败
        if (isset($payload['exp']) && $payload['exp'] < time())
            return false;
        //该nbf时间之前不接收处理该Token
        if (isset($payload['nbf']) && $payload['nbf'] > time())
            return false;
        return $payload;
    }

    /**
     * base64UrlEncode   https://jwt.io/  中base64UrlEncode编码实现
     * @param string $input 需要编码的字符串
     * @return string
     */
    private static function base64UrlEncode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * base64UrlEncode  https://jwt.io/  中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    private static function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * HMACSHA256签名   https://jwt.io/  中HMACSHA256签名实现
     * @param string $input 为base64UrlEncode(header).".".base64UrlEncode(payload)
     * @param string $key
     * @param string $alg 算法方式
     * @return mixed
     */
    private static function signature($input, $key, $alg = 'HS256')
    {
        $alg_config = array(
            'HS256' => 'sha256'
        );
        return self::base64UrlEncode(hash_hmac($alg_config[$alg], $input, $key, true));
    }
}

/**
 * 配置文件管理
 */
class Config
{
    static private $_config = [
        'APP_PATH'      => 'App',
        'APP_NAMESPACE' => 'App',  # 业务代码的主命名空间 namespace
        'CTL_NAMESPACE' => 'Controller',   # 控制器代码的命名空间 namespace
        'IS_CACHE'      => true,
        'CACHE_TIME'    => 3600,
        'LOG_LEVEL'     => 2
    ];

    /**
     * 初始化
     */
    static public function Init()
    {
        $Config_path = ROOT_PATH . DIRECTORY_SEPARATOR . 'Config';
        is_dir($Config_path) ?: creatdir($Config_path);
        $subdirs = [];
        if (!$dh = opendir($Config_path))
            return $subdirs;
        $i = 0;
        while ($f = readdir($dh)) {
            if ($f == '.' || $f == '..')
                continue;
            $Config_file_path = $Config_path . DIRECTORY_SEPARATOR . $f . DIRECTORY_SEPARATOR . '.php';
            if (file_exists($Config_file_path)) {
                $subdirs[$i] = require $Config_file_path;
                $i++;
            }
        }
        static::$_config = array_merge(static::$_config, $subdirs);
        return static::$_config;
    }

    /**
     * 获取所有配置
     */
    static function all()
    {
        return static::$_config;
    }

    /**
     * 获取配置值
     */
    static function get($key)
    {
        if (is_string($key))     //如果传入的key是字符串
            return value(static::$_config, $key);
        if (is_array($key)) {
            if (array_keys($key) !== range(0, count($key) - 1))      //如果传入的key是关联数组
                static::$_config = array_merge(static::$_config, $key);
            else {
                $ret = [];
                foreach ($key as $k)
                    $ret[$k] = value(static::$_config, $k);
                return $ret;
            }
        }
    }

    /**
     * 设置配置
     */
    static function set($key, $value = null)
    {
        if (is_string($key))
            static::$_config[$key] = $value;
        else
            Halt('传入参数不正确');
    }
}

/**
 * 控制器类
 */
class Controller
{
    private $_cache;

    protected function _init()
    {
    }

    /**
     * 构造函数，初始化视图实例，调用hook
     */
    public function __construct()
    {
        $this->start_cache();
        $this->_init();
        $plugin = new Plugin();
    }

    /**
     * 开启缓存
     */
    protected function start_cache()
    {
        $is_cache = Config::get('IS_CACHE');
        if ($is_cache) {
            $cache_path = ROOT_PATH . 'runtime' . DIRECTORY_SEPARATOR . 'cache';
            is_dir($cache_path) ?: creatdir($cache_path);
            Cache::Init();
        }
    }

    /**
     * 将数据用json格式输出至浏览器，并停止执行代码
     * @param array|string|object $data 要输出的数据
     */
    protected function json($json)
    {
        sp_output(sp_tojson($json), "application/json");
    }

    protected function xml($xmlstr)
    {
        sp_output($xmlstr, "text/xml");
    }

    protected function text($textstr)
    {
        sp_output($textstr, "text/plain");
    }
}

/**
 * 通用控制器
 */
class BaseController extends Controller
{
    private $_view;

    // 视图实例
    protected function _init()
    {
        $this->_view = new View();
        header("Content-Type: text/html; charset=utf-8");
    }

    /**
     * 渲染模板并输出
     * @param null|string $tpl 模板文件路径
     * 参数为相对于App/View/文件的相对路径，不包含后缀名，例如index/index
     * 如果参数为空，则默认使用$controller/$action.php
     * 如果参数不包含"/"，则默认使用$controller/$tpl
     */
    protected function display($tpl = '')
    {
        if ($tpl === '')
            $tpl = MODULE_NAME . '/' . ACTION_NAME; elseif (strpos($tpl, '/') === false)
            $tpl = MODULE_NAME . '/' . $tpl;
        $this->_view->display($tpl);
    }

    /**
     * 为视图引擎设置一个模板变量
     * @param string $name 要在模板中使用的变量名
     * @param mixed $value 模板中该变量名对应的值
     */
    protected function assign($name, $value)
    {
        $this->_view->assign($name, $value);
    }

    protected function redirect($url)
    {
        header("Location: $url");
        exit();
    }
}

/**
 * Restful 控制器
 */
class RestfulController extends Controller
{
    use Jwt;

    public function Get()
    {
    }

    public function Post()
    {
    }

    public function Put()
    {
    }

    public function Delete()
    {
    }

    // 获取post、put传的json对象
    protected function read()
    {
        return json_decode(file_get_contents('php://input'));
    }
}

/**
 * Api 控制器
 */
class ApiController extends Controller
{
    use Jwt;

    // 获取post、put传的json对象
    protected function read()
    {
        return json_decode(file_get_contents('php://input'));
    }
}

/**
 * 视图类
 */
class View
{
    private $_tplDir;
    /** 视图文件目录 */
    private $_tplCacheDir;
    /** 编译后模板缓存目录 */
    private $_viewPath;
    /** 视图文件路径 */
    private $_data = [];
    /** 视图变量列表 */
    /**
     * @param string $tplDir 模板目录
     */
    public function __construct($tplDir = '')
    {
        $this->_tplDir = $tplDir ?: APP_FULL_PATH . '/View/';
        $this->_tplCacheDir = ROOT_PATH . '/runtime/Tpl/';
        is_dir($this->_tplDir) ?: creatdir($this->_tplDir);
        is_dir($this->_tplCacheDir) ?: creatdir($this->_tplCacheDir);
    }

    /**
     * 为视图引擎设置一个模板变量
     * @param string $key 要在模板中使用的变量名
     * @param mixed $value 模板中该变量名对应的值
     */
    public function assign($key, $value)
    {
        $this->_data[$key] = $value;
    }

    /**
     * 渲染模板并输出
     * 2017-06-25 加入模板缓存, 注意<include文件变化不会主动更新，可以清除缓存，或更新包含文件。
     * @param null|string $tplFile 模板文件路径，相对于App/View/文件的相对路径，不包含后缀名，例如index/index
     */
    public function display($tplFile)
    {
        $this->_viewPath = $this->_tplDir . $tplFile . '.php';
        $cacheTplFile = $this->_tplCacheDir . md5($tplFile) . ".php";
        if (!is_file($cacheTplFile) || filemtime($this->_viewPath) > filemtime($cacheTplFile))
            file_put_contents($cacheTplFile, $this->compiler($this->_viewPath));
        unset($tplFile);
        extract($this->_data);
        include $cacheTplFile;
    }

    /**
     * 编译模板文件
     */
    protected function compiler($tplfile, $flag = true)
    {
        $content = file_get_contents($tplfile);
        // 添加安全代码 代表入口文件进入的
        if ($flag)
            $content = '<?php if (!defined(\'APP_FULL_PATH\')) exit();?>' . $content;
        $content = preg_replace(
            array(
                '/{\$([^\}]+)}/s', // 匹配 {$vo['info']}  '/{\$([\w\[\]\'"\$]+)}/s'
                '/{\:Url([^\}]+)}/s', // 匹配 {:Url("")}, 纯简化
                '/{\:([^\}]+)}/s', // 匹配 {:func($vo['info'])}
                '/<each[ ]+[\'"](.+?)[\'"][ ]*>/', // 匹配 <each "$list as $v"></each>
                '/<if[ ]*[\'"](.+?)[\'"][ ]*>/', // 匹配 <if "$key == 1"></if>
                '/<elseif[ ]*[\'"](.+?)[\'"][ ]*>/',
            ),
            array(
                '<?php echo $\\1;?>',
                '<?php echo \\SinglePHP\\Url\\1;?>',
                '<?php echo \\1;?>',
                '<?php foreach( \\1 ){ ?>',
                '<?php if( \\1 ){ ?>',
                '<?php }elseif( \\1 ){ ?>',
            ),
            $content);
        $content = str_replace(array( '</if>', '<else/>', '</each>', 'APP_URL', 'MODULE_NAME', 'ACTION_NAME' ),
            array( '<?php } ?>', '<?php }else{ ?>', '<?php } ?>', APP_URL, MODULE_NAME, ACTION_NAME ), $content);
        // 匹配 <include "Public/Menu"/>
        $content = preg_replace_callback('/<include[ ]+[\'"](.+)[\'"][ ]*\/>/',
            function ($matches) {
                return $this->compiler($this->_tplDir . $matches[1] . '.php', false);
            }
            , $content);
        return $content;
    }
}

/**
 * 插件机制的实现核心类
 */
class Plugin
{
    private static $_instance = [];
    /** 实例数组 */
    /**
     * 监听已注册的插件
     *
     * @access private
     * @var array
     */
    private static $_listeners = [];

    static public function getInstance()
    {
        if (!isset(self::$_instance) || !(self::$_instance instanceof self))
            self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * 构造函数
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        #这里$plugin数组包含我们获取已经由用户激活的插件信息
        #为演示方便，我们假定$plugin中至少包含
        #$plugin = array(
        #  'title' => '插件标题',
        #  'name'  => '插件名称'
        #);
        $plugins = self::get_active_plugins();
        if ($plugins) {

            foreach ($plugins as $plugin) {

                //假定每个插件文件夹中包含一个actions.php文件，它是插件的具体实现
                $plugins_file_name = ROOT_PATH  . 'Plugins' . DIRECTORY_SEPARATOR . $plugin . DIRECTORY_SEPARATOR . $plugin . '.php';
                if (@file_exists($plugins_file_name)) {
                    include_once($plugins_file_name);
                    $class = '\\Plugins\\' . $plugin.'\\'.$plugin;
                    if (class_exists($class)) {
                        //初始化所有插件
                        new $class($this);
                    }
                }
            }
        }
        #此处做些日志记录方面的东西
    }

    /**
     * 获取插件列表
     */
    private static function get_active_plugins()
    {
        $Plugins_path = ROOT_PATH .  'Plugins';
        is_dir($Plugins_path) ?: creatdir($Plugins_path);
        $subdirs = [];
        if (!$dh = opendir($Plugins_path))
            return $subdirs;
        $i = 0;
        while ($f = readdir($dh)) {
            if ($f == '.' || $f == '..')
                continue;
//
//            $Plugin_path = $Plugins_path . DIRECTORY_SEPARATOR . $f . DIRECTORY_SEPARATOR . 'info.php';
//            if (file_exists($Plugin_path)) {
//                $subdirs[$i] = require $Plugin_path;
//                $i++;
//            }
            $subdirs[$i] = $f;
        }
        return $subdirs;
    }

    /**
     * 注册需要监听的插件方法（钩子）
     *
     * @param string $hook
     * @param object $reference
     * @param string $method
     */
    function register($hook, &$reference, $method)
    {
        //获取插件要实现的方法
        $key = get_class($reference) . '->' . $method;
        //将插件的引用连同方法push进监听数组中
        static::$_listeners[$hook][$key] = array( &$reference, $method );
        #此处做些日志记录方面的东西
    }

    /**
     * 触发一个钩子
     *
     * @param string $hook 钩子的名称
     * @param mixed $data 钩子的入参
     * @return mixed
     */
    static function trigger($hook, $data = '')
    {
        $result = '';
        //查看要实现的钩子，是否在监听数组之中
        if (isset(static::$_listeners[$hook]) && is_array(static::$_listeners[$hook]) && count(static::$_listeners[$hook]) > 0) {
            // 循环调用开始
            foreach (static::$_listeners[$hook] as $listener) {
                // 取出插件对象的引用和方法
                $class =& $listener[0];
                $method = $listener[1];
                if (method_exists($class, $method)) {
                    // 动态调用插件的方法
                    $result .= $class->$method($data);
                }
            }
        }
        #此处做些日志记录方面的东西
        return $result;
    }
}
class Plugin_{
    const PLUGIN_TITLE = '';
    const PLUGIN_NAME  = '';
    //解析函数的参数是pluginManager的引用
    function __construct(&$plugin)
    {
        //注册这个插件
        //第一个参数是钩子的名称
        //第二个参数是pluginManager的引用
        //第三个是插件所执行的方法
        $plugin->register(static::PLUGIN_NAME, $this, 'action');
    }
}

/**
 * 数据库操作类
 * 使用方法：
 *      $db = db();
 *      $db->query('select * from table');
 * 2015-06-25 数据库操作改为PDO，可以用于php7. 或者使用 Medoo，支持多种数据库
 */
class DB
{
    private static $_instance = [];
    /** 实例数组 */
    private $_db;
    /** 数据库链接 */
    private $_db_type;
    /** 数据库类型 */
    private $_lastSql;
    /** 保存最后一条sql */
    private $_allSql;
    public $_tbl_prefix = '';
    /** 表名前缀 */
    private $autocount = false, $pagesize = 20, $pageno = -1, $totalrows = -1;
    /** 是否自动计算总数，页数，页大小，总条数 */
    /**
     * DB 构造函数
     * @param array $dbConf 配置数组
     * @throws \Exception
     */
    private function __construct($dbConf)
    {
        try {
            $this->_db = new \PDO($dbConf['DB_DSN'], $dbConf["DB_USER"], $dbConf["DB_PWD"], $dbConf['DB_OPTIONS']) or exit ('数据库连接创建失败');
            $this->_db_type = strtolower(strstr($dbConf["DB_DSN"], ':', true));
            $this->_tbl_prefix = value($dbConf, "TBL_PREFIX", "");
        } catch (\PDOException $e) {
            // 避免泄露密码等
            throw new \Exception($e->getMessage());
        }
    }

    /** 获取DB类
     * @param array $dbConf 配置数组
     * @return DB
     * @throws \Exception
     */
    static public function getInstance($dbConf)
    {
        $key = sp_tojson($dbConf);
        if (!isset(self::$_instance[$key]) || !(self::$_instance[$key] instanceof self))
            self::$_instance[$key] = new self($dbConf);
        return self::$_instance[$key];
    }

    public function beginTransaction()
    {
        $this->_db->beginTransaction();
    }

    public function commit()
    {
        $this->_db->commit();
    }

    public function rollBack()
    {
        $this->_db->rollBack();
    }

    /**
     * 转义字符串
     * @param string $str 要转义的字符串
     * @return string 转义后的字符串
     */
    public function escape($str)
    {
        return $this->_db->quote($str);
    }

    public function close()
    {
        $this->_db = NULL;
    }

    public function select($sql, $bind = [])
    {
        if ($this->pageno > 0) {
            $pagers = [];
            if ($this->autocount) {
                $sqlcount = preg_replace("/select[\s(].+?[\s)]from([\s(])/is", "SELECT COUNT(*) AS num FROM $1", $sql, 1);
                $total = $this->execute($sqlcount, $bind, 'select');
                $this->totalrows = empty($total) ? 0 : $total[0]["num"];
            }
            if ($this->totalrows != 0) {
                if (in_array($this->_db_type, [ 'oci', 'sqlsrv', 'firebird' ])) {
                    // oracle12c mssql2008 firebird3
                    if ($this->pageno == 1)
                        $sql .= ' FETCH FIRST ' . $this->pagesize . ' ROWS ONLY'; else
                        $sql .= ' OFFSET ' . ($this->pagesize * ($this->pageno - 1)) . ' ROWS FETCH NEXT ' . $this->pagesize . ' ROWS ONLY';
                } else {
                    // mysql sqlite pgsql HSQLDB H2
                    if ($this->pageno == 1)
                        $sql .= ' LIMIT ' . $this->pagesize; else
                        $sql .= ' LIMIT ' . $this->pagesize . ' OFFSET ' . ($this->pagesize * ($this->pageno - 1));
                }
                $pagers = $this->execute($sql, $bind, 'select');
            }
            $this->autocount = false;
            $this->pagesize = 20;
            $this->pageno = -1;
            $this->totalrows = -1;
            return $pagers;
        } else
            return $this->execute($sql, $bind, 'select');
    }

    public function insert($sql, $bind = [])
    {
        return $this->execute($sql, $bind, 'insert');
    }

    public function update($sql, $bind = [])
    {
        return $this->execute($sql, $bind, 'update');
    }

    public function delete($sql, $bind = [])
    {
        return $this->execute($sql, $bind, 'delete');
    }

    /** 执行sql语句
     * @param string $sql 要执行的sql
     * @param array $bind 执行中的参数
     * @return bool|int|array 执行成功返回数组、数量、自增id，失败返回false
     * @throws \Exception
     */
    private function execute($sql, $bind = [], $flag = '')
    {
        $this->_lastSql = $sql;
        $this->_allSql[] = $sql;
        try {
            $stmt = $this->_db->prepare($sql);
            if (!$stmt)
                $this->error($this->_db, $sql);
            foreach ($bind as $k => $v)
                $stmt->bindValue($k, $v);
            if (!$stmt->execute())
                $this->error($stmt, $sql);
            switch ($flag) {
                case 'insert':
                    {
                        if ("pgsql" == $this->_db_type) {
                            // id SERIAL PRIMARY KEY,
                            if (preg_match("/^INSERT[\t\n ]+INTO[\t\n ]+([a-z0-9\_\-]+)/is", $sql, $tablename))
                                return $this->_db->lastInsertId($tablename[1] . '_id_seq');
                        }
                        return $this->_db->lastInsertId();
                    }
                    break;
                case 'update':
                case 'delete':
                    return $stmt->rowCount();
                    break;
                case 'select':
                    return $stmt->fetchAll(\PDO::FETCH_ASSOC);
                    break;
                default:
                    break;
            }
        } catch (\PDOException $e) {
            $this->error($e, $sql);
        }
    }

    private function error($e, $sql)
    {
        throw new \Exception(implode(', ', $e->errorInfo) . "\n[SQL]：" . $sql);
    }

    function __destruct()
    {
        $this->_db = null;
    }

    public function getLastSql()
    {
        return $this->_lastSql;
    }

    public function getAllSql()
    {
        return $this->_allSql;
    }

    public function totalrows()
    {
        return $this->totalrows;
    }

    public function autocount()
    {
        $this->autocount = true;
        return $this;
    }

    /** 分页参数
     * @param number $pageno
     * @param number $pagesize
     * @return DB
     */
    public function page($pageno, $pagesize = 20)
    {
        $this->pageno = $pageno;
        $this->pagesize = $pagesize;
        return $this;
    }
}

/**
 * 数据库模型, 简化增删改查
 * $model = new UserModel("tablename");
 * $model->where("sqlwhere conditon", array(vvv))->get();
 */
class Model
{
    protected $_db = null;
    // 数据库连接
    protected $_table = '';
    // 表名
    protected $_pk = '';
    // 主键名
    protected $_where = '';
    // where语句
    protected $_bind = [];

    // 参数数组
    function __construct($tbl_name = '', $db_name = '', $pk = "id", $db = null)
    {
        $this->_initialize();
        if ($this->_db == null) $this->_db = $db ?: db();
        $this->_table = (empty($db_name) ? "" : $db_name . '.') . $this->_db->_tbl_prefix . ($this->_table ?: $tbl_name);
        if (empty($this->_pk)) $this->_pk = $pk;
    }

    // 回调方法 初始化模型
    protected function _initialize()
    {
    }

    /** where条件
     * @param string|array $sqlwhere sql条件|或查询数组
     * @param array $bind 参数数组
     * @return Model $this
     * @throws \Exception
     */
    public function where($sqlwhere, $bind = [])
    {
        if (is_array($sqlwhere)) {
            $item = [];
            $this->_bind = [];
            foreach ($sqlwhere as $k => $v) {
                if (substr($k, 0, 1) == '_') continue;
                if (is_array($v)) {
                    $exp = strtoupper($v[0]);
                    //  in like
                    if (preg_match('/^(NOT IN|IN)$/', $exp)) {
                        if (is_string($v[1])) $v[1] = explode(',', $v[1]);
                        $vals = implode(',', $this->_db->quote($v[1]));
                        $item[] = "$k $exp ($vals)";
                    } elseif (preg_match('/^(=|!=|<|<>|<=|>|>=)$/', $exp)) {
                        $k1 = count($this->_bind);
                        $item[] = "$k $exp :$k1";
                        $this->_bind[":$k1"] = $v[1];
                    } elseif (preg_match('/^(BETWEEN|NOT BETWEEN)$/', $exp)) {
                        $tmp = is_string($v[1]) ? explode(',', $v[1]) : $v[1];
                        $k1 = count($this->_bind);
                        $k2 = $k1 + 1;
                        $item[] = "($k $exp :$k1 AND :$k2)";
                        $this->_bind[":$k1"] = $tmp[0];
                        $this->_bind[":$k2"] = $tmp[1];
                    } elseif (preg_match('/^(LIKE|NOT LIKE)$/', $exp)) {
                        $wyk = ':' . count($this->_bind);
                        $item[] = "$k $exp $wyk";
                        $this->_bind[$wyk] = $v[1];
                    } else {
                        throw new \Exception("exp error", 1);
                    }
                } else {
                    $wyk = ':' . count($this->_bind);
                    $item[] = "$k=$wyk";
                    $this->_bind[$wyk] = $v;
                }
            }
            $this->_where = ' (' . implode(" AND ", $item) . ') ';
            $this->_where .= value($sqlwhere, "_sql", "");
            // 其他如order group等语句
        } else {
            $this->_where = $sqlwhere;
            $this->_bind = $bind;
        }
        return $this;
    }

    /** 获取一条记录
     * @param null|number $id
     * @return boolean|array
     * @throws \Exception
     */
    public function get($id = null)
    {
        if ($id != null)
            $this->where(array( $this->_pk => $id ));
        $info = $this->select();
        return count($info) > 0 ? $info[0] : $info;
    }

    /** 获取多条记录
     * @return boolean|array
     */
    public function select()
    {
        $_sql = 'SELECT * FROM ' . $this->_table . " WHERE " . $this->_where;
        $info = $this->_db->select($_sql, $this->_bind);
        $this->clean();
        return $info;
    }

    /** 更新数据
     * @param array $data
     * @return boolean|number
     * @throws \Exception
     */
    public function update($data)
    {
        if (isset($data[$this->_pk])) {
            $this->where($this->_pk . "=:" . $this->_pk, array( ":" . $this->_pk => $data[$this->_pk] ));
            unset($data[$this->_pk]);
        }
        if (empty($this->_where))
            return false;
        $keys = '';
        $_bind = [];
        foreach ($data as $k => $v) {
            $keys .= "$k=:$k,";
            $_bind[":$k"] = $v;
        }
        $keys = substr($keys, 0, -1);
        $this->_bind = array_merge($this->_bind, $_bind);
        $_sql = 'UPDATE ' . $this->_table . " SET {$keys} WHERE " . $this->_where;
        $info = $this->_db->update($_sql, $this->_bind);
        $this->clean();
        return $info;
    }

    /** 删除数据
     * @param null|number $id
     * @return boolean|number
     * @throws \Exception
     */
    public function delete($id = null)
    {
        if ($id != null)
            $this->where(array( $this->_pk => $id ));
        $_sql = 'DELETE FROM ' . $this->_table . " WHERE " . $this->_where;
        $info = $this->_db->delete($_sql, $this->_bind);
        $this->clean();
        return $info;
    }

    /** 插入数组，字段名=>值
     * @param array $data
     * @return boolean|number
     */
    public function insert($data)
    {
        $keys = '';
        $vals = '';
        $_bind = [];
        foreach ($data as $k => $v) {
            if (is_null($v)) continue;
            $keys .= "$k,";
            $vals .= ":$k,";
            $_bind[":$k"] = $v;
        }
        $keys = substr($keys, 0, -1);
        $vals = substr($vals, 0, -1);
        $_sql = 'INSERT INTO ' . $this->_table . " ($keys) VALUES ($vals)";
        return $this->_db->insert($_sql, $_bind);
    }

    private function clean()
    {
        $this->_where = "";
        $this->_bind = [];
    }
}

/**
 * 数据缓存类
 */
class Cache
{
    public static $cache_path = '';
    //path for the cache
    public static $cache_expire = '';

    //seconds that the cache expires
    public static function Init($cache_path = '', $exp_time = 3600)
    {
        static::$cache_expire = $exp_time !== 3600 ? $exp_time : 3600;
        static::$cache_path = $cache_path !== '' ? $cache_path : APP_FULL_PATH . '/../runtime/cache/';
        if (!is_dir(static::$cache_path)) {
            creatdir(static::$cache_path);
        }
    }

    /**
     * 检查是否开启缓存
     */
    public static function CheckCache()
    {
        if (Config::get('IS_CACHE') !== true) {
            throw new \Exception("未开启缓存!");
        }
    }

    //returns the filename for the cache
    private static function fileName($key)
    {
        return static::$cache_path . md5($key);
    }

    //creates new cache files with the given data, $key== name of the cache, data the info/values to store
    public static function set($key, $data)
    {
        static::CheckCache();
        $values = serialize($data);
        $filename = static::fileName($key);
        $file = fopen($filename, 'w');
        if ($file) {
            //able to create the file
            fwrite($file, $values);
            fclose($file);
        } else {
            return false;
        }
    }

    //returns cache for the given key
    public static function get($key)
    {
        static::CheckCache();
        $filename = static::fileName($key);
        if (!file_exists($filename) || !is_readable($filename)) {
            //can't read the cache
            return false;
        }
        if (time() < (filemtime($filename) + static::$cache_expire)) {
            //cache for the key not expired
            $file = fopen($filename, "r");
            // read data file
            if ($file) {
                //able to open the file
                $data = fread($file, filesize($filename));
                fclose($file);
                return unserialize($data);
                //return the values
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}

/**
 * 日志类
 * 使用方法：Log::error('error msg');
 * 保存路径为 App/Log，按天存放
 */
class Log
{
    const DEBUG = 1, NOTICE = 2, WARN = 3, ERROR = 4, FATAL = 5;

    /**
     * 打印日志
     * @param string $msg 日志内容
     * @param string $level 日志等级
     */
    protected static function write($msg, $level = Log::NOTICE)
    {
        if (null != Config::get('LOG_LEVEL') && Config::get('LOG_LEVEL') <= $level) {
            $msg = date('[ Y-m-d H:i:s ]') . " [{$level}] " . $msg . "\r\n";
            $logPath = ROOT_PATH . DIRECTORY_SEPARATOR . 'Log' . DIRECTORY_SEPARATOR . date('Ymd') . '.log';
            is_dir($logPath) ?: creatdir(dirname($logPath));
            file_put_contents($logPath, $msg, FILE_APPEND);
        }
    }

    /** 打印fatal日志
     * @param string $msg 日志信息
     */
    public static function fatal($msg)
    {
        self::write($msg, Log::FATAL);
    }

    public static function error($msg)
    {
        self::write($msg, Log::ERROR);
    }

    public static function warn($msg)
    {
        self::write($msg, Log::WARN);
    }

    public static function notice($msg)
    {
        self::write($msg, Log::NOTICE);
    }

    public static function debug($msg)
    {
        self::write($msg, Log::DEBUG);
    }
}

/*!
 * Medoo database framework
 * https://medoo.in
 * Version 1.6
 *
 * Copyright 2018, Angel Lai
 * Released under the MIT license
 */

use PDO;
use Exception;
use PDOException;
use InvalidArgumentException;

class Raw
{
    public $map;
    public $value;
}

class Medoo
{
    public $pdo;
    protected $type;
    protected $prefix;
    protected $statement;
    protected $dsn;
    protected $logs = [];
    protected $logging = false;
    protected $debug_mode = false;
    protected $guid = 0;

    public function __construct()
    {
        $options = Config::get('DB');
        if (isset($options['database_type'])) {
            $this->type = strtolower($options['database_type']);
            if ($this->type === 'mariadb') {
                $this->type = 'mysql';
            }
        }
        if (isset($options['prefix'])) {
            $this->prefix = $options['prefix'];
        }
        if (isset($options['logging']) && is_bool($options['logging'])) {
            $this->logging = $options['logging'];
        }
        $option = isset($options['option']) ? $options['option'] : [];
        $commands = (isset($options['command']) && is_array($options['command'])) ? $options['command'] : [];
        switch ($this->type) {
            case 'mysql':
                // Make MySQL using standard quoted identifier
                $commands[] = 'SET SQL_MODE=ANSI_QUOTES';
                break;
            case 'mssql':
                // Keep MSSQL QUOTED_IDENTIFIER is ON for standard quoting
                $commands[] = 'SET QUOTED_IDENTIFIER ON';
                // Make ANSI_NULLS is ON for NULL value
                $commands[] = 'SET ANSI_NULLS ON';
                break;
        }
        if (isset($options['pdo'])) {
            if (!$options['pdo'] instanceof PDO) {
                throw new InvalidArgumentException('Invalid PDO object supplied');
            }
            $this->pdo = $options['pdo'];
            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }
            return;
        }
        if (isset($options['dsn'])) {
            if (is_array($options['dsn']) && isset($options['dsn']['driver'])) {
                $attr = $options['dsn'];
            } else {
                throw new InvalidArgumentException('Invalid DSN option supplied');
            }
        } else {
            if (
                isset($options['port']) &&
                is_int($options['port'] * 1)
            ) {
                $port = $options['port'];
            }
            $is_port = isset($port);
            switch ($this->type) {
                case 'mysql':
                    $attr = [
                        'driver' => 'mysql',
                        'dbname' => $options['database_name']
                    ];
                    if (isset($options['socket'])) {
                        $attr['unix_socket'] = $options['socket'];
                    } else {
                        $attr['host'] = $options['server'];
                        if ($is_port) {
                            $attr['port'] = $port;
                        }
                    }
                    break;
                case 'pgsql':
                    $attr = [
                        'driver' => 'pgsql',
                        'host'   => $options['server'],
                        'dbname' => $options['database_name']
                    ];
                    if ($is_port) {
                        $attr['port'] = $port;
                    }
                    break;
                case 'sybase':
                    $attr = [
                        'driver' => 'dblib',
                        'host'   => $options['server'],
                        'dbname' => $options['database_name']
                    ];
                    if ($is_port) {
                        $attr['port'] = $port;
                    }
                    break;
                case 'oracle':
                    $attr = [
                        'driver' => 'oci',
                        'dbname' => $options['server'] ?
                            '//' . $options['server'] . ($is_port ? ':' . $port : ':1521') . '/' . $options['database_name'] :
                            $options['database_name']
                    ];
                    if (isset($options['charset'])) {
                        $attr['charset'] = $options['charset'];
                    }
                    break;
                case 'mssql':
                    if (isset($options['driver']) && $options['driver'] === 'dblib') {
                        $attr = [
                            'driver' => 'dblib',
                            'host'   => $options['server'] . ($is_port ? ':' . $port : ''),
                            'dbname' => $options['database_name']
                        ];
                        if (isset($options['appname'])) {
                            $attr['appname'] = $options['appname'];
                        }
                        if (isset($options['charset'])) {
                            $attr['charset'] = $options['charset'];
                        }
                    } else {
                        $attr = [
                            'driver'   => 'sqlsrv',
                            'Server'   => $options['server'] . ($is_port ? ',' . $port : ''),
                            'Database' => $options['database_name']
                        ];
                        if (isset($options['appname'])) {
                            $attr['APP'] = $options['appname'];
                        }
                        $config = [
                            'ApplicationIntent',
                            'AttachDBFileName',
                            'Authentication',
                            'ColumnEncryption',
                            'ConnectionPooling',
                            'Encrypt',
                            'Failover_Partner',
                            'KeyStoreAuthentication',
                            'KeyStorePrincipalId',
                            'KeyStoreSecret',
                            'LoginTimeout',
                            'MultipleActiveResultSets',
                            'MultiSubnetFailover',
                            'Scrollable',
                            'TraceFile',
                            'TraceOn',
                            'TransactionIsolation',
                            'TransparentNetworkIPResolution',
                            'TrustServerCertificate',
                            'WSID',
                        ];
                        foreach ($config as $value) {
                            $keyname = strtolower(preg_replace([ '/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/' ], '$1_$2', $value));
                            if (isset($options[$keyname])) {
                                $attr[$value] = $options[$keyname];
                            }
                        }
                    }
                    break;
                case 'sqlite':
                    $attr = [
                        'driver' => 'sqlite',
                        $options['database_file']
                    ];
                    break;
            }
        }
        if (!isset($attr)) {
            throw new InvalidArgumentException('Incorrect connection options');
        }
        $driver = $attr['driver'];
        if (!in_array($driver, PDO::getAvailableDrivers())) {
            throw new InvalidArgumentException("Unsupported PDO driver: {$driver}");
        }
        unset($attr['driver']);
        $stack = [];
        foreach ($attr as $key => $value) {
            $stack[] = is_int($key) ? $value : $key . '=' . $value;
        }
        $dsn = $driver . ':' . implode(';', $stack);
        if (
            in_array($this->type, [ 'mysql', 'pgsql', 'sybase', 'mssql' ]) &&
            isset($options['charset'])
        ) {
            $commands[] = "SET NAMES '{$options[ 'charset' ]}'" . (
                $this->type === 'mysql' && isset($options['collation']) ? " COLLATE '{$options[ 'collation' ]}'" : ''
                );
        }
        $this->dsn = $dsn;
        try {
            $this->pdo = new PDO(
                $dsn,
                isset($options['username']) ? $options['username'] : null,
                isset($options['password']) ? $options['password'] : null,
                $option
            );
            foreach ($commands as $value) {
                $this->pdo->exec($value);
            }
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage());
        }
    }

    public function query($query, $map = [])
    {
        $raw = $this->raw($query, $map);
        $query = $this->buildRaw($raw, $map);
        return $this->exec($query, $map);
    }

    public function exec($query, $map = [])
    {
        if ($this->debug_mode) {
            echo $this->generate($query, $map);
            $this->debug_mode = false;
            return false;
        }
        if ($this->logging) {
            $this->logs[] = [ $query, $map ];
        } else {
            $this->logs = [ [ $query, $map ] ];
        }
        $statement = $this->pdo->prepare($query);
        if ($statement) {
            foreach ($map as $key => $value) {
                $statement->bindValue($key, $value[0], $value[1]);
            }
            $statement->execute();
            $this->statement = $statement;
            return $statement;
        }
        return false;
    }

    protected function generate($query, $map)
    {
        $identifier = [
            'mysql' => '`$1`',
            'mssql' => '[$1]'
        ];
        $query = preg_replace(
            '/"([a-zA-Z0-9_]+)"/i',
            isset($identifier[$this->type]) ? $identifier[$this->type] : '"$1"',
            $query
        );
        foreach ($map as $key => $value) {
            if ($value[1] === PDO::PARAM_STR) {
                $replace = $this->quote($value[0]);
            } elseif ($value[1] === PDO::PARAM_NULL) {
                $replace = 'NULL';
            } elseif ($value[1] === PDO::PARAM_LOB) {
                $replace = '{LOB_DATA}';
            } else {
                $replace = $value[0];
            }
            $query = str_replace($key, $replace, $query);
        }
        return $query;
    }

    public static function raw($string, $map = [])
    {
        $raw = new Raw();
        $raw->map = $map;
        $raw->value = $string;
        return $raw;
    }

    protected function isRaw($object)
    {
        return $object instanceof Raw;
    }

    protected function buildRaw($raw, &$map)
    {
        if (!$this->isRaw($raw)) {
            return false;
        }
        $query = preg_replace_callback(
            '/((FROM|TABLE|INTO|UPDATE)\s*)?\<([a-zA-Z0-9_\.]+)\>/i',
            function ($matches) {
                if (!empty($matches[2])) {
                    return $matches[2] . ' ' . $this->tableQuote($matches[3]);
                }
                return $this->columnQuote($matches[3]);
            }
            ,
            $raw->value);
        $raw_map = $raw->map;
        if (!empty($raw_map)) {
            foreach ($raw_map as $key => $value) {
                $map[$key] = $this->typeMap($value, gettype($value));
            }
        }
        return $query;
    }

    public function quote($string)
    {
        return $this->pdo->quote($string);
    }

    protected function tableQuote($table)
    {
        return '"' . $this->prefix . $table . '"';
    }

    protected function mapKey()
    {
        return ':MeDoO_' . $this->guid++ . '_mEdOo';
    }

    protected function typeMap($value, $type)
    {
        $map = [
            'NULL'     => PDO::PARAM_NULL,
            'integer'  => PDO::PARAM_INT,
            'double'   => PDO::PARAM_STR,
            'boolean'  => PDO::PARAM_BOOL,
            'string'   => PDO::PARAM_STR,
            'object'   => PDO::PARAM_STR,
            'resource' => PDO::PARAM_LOB
        ];
        if ($type === 'boolean') {
            $value = ($value ? '1' : '0');
        } elseif ($type === 'NULL') {
            $value = null;
        }
        return [ $value, $map[$type] ];
    }

    protected function columnQuote($string)
    {
        if (strpos($string, '.') !== false) {
            return '"' . $this->prefix . str_replace('.', '"."', $string) . '"';
        }
        return '"' . $string . '"';
    }

    protected function columnPush(&$columns, &$map)
    {
        if ($columns === '*') {
            return $columns;
        }
        $stack = [];
        if (is_string($columns)) {
            $columns = [ $columns ];
        }
        foreach ($columns as $key => $value) {
            if (is_array($value)) {
                $stack[] = $this->columnPush($value, $map);
            } elseif (!is_int($key) && $raw = $this->buildRaw($value, $map)) {
                preg_match('/(?<column>[a-zA-Z0-9_\.]+)(\s*\[(?<type>(String|Bool|Int|Number))\])?/i', $key, $match);
                $stack[] = $raw . ' AS ' . $this->columnQuote($match['column']);
            } elseif (is_int($key) && is_string($value)) {
                preg_match('/(?<column>[a-zA-Z0-9_\.]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/i', $value, $match);
                if (!empty($match['alias'])) {
                    $stack[] = $this->columnQuote($match['column']) . ' AS ' . $this->columnQuote($match['alias']);
                    $columns[$key] = $match['alias'];
                    if (!empty($match['type'])) {
                        $columns[$key] .= ' [' . $match['type'] . ']';
                    }
                } else {
                    $stack[] = $this->columnQuote($match['column']);
                }
            }
        }
        return implode(',', $stack);
    }

    protected function arrayQuote($array)
    {
        $stack = [];
        foreach ($array as $value) {
            $stack[] = is_int($value) ? $value : $this->pdo->quote($value);
        }
        return implode(',', $stack);
    }

    protected function innerConjunct($data, $map, $conjunctor, $outer_conjunctor)
    {
        $stack = [];
        foreach ($data as $value) {
            $stack[] = '(' . $this->dataImplode($value, $map, $conjunctor) . ')';
        }
        return implode($outer_conjunctor . ' ', $stack);
    }

    protected function dataImplode($data, &$map, $conjunctor)
    {
        $stack = [];
        foreach ($data as $key => $value) {
            $type = gettype($value);
            if (
                $type === 'array' &&
                preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relation_match)
            ) {
                $relationship = $relation_match[1];
                $stack[] = $value !== array_keys(array_keys($value)) ?
                    '(' . $this->dataImplode($value, $map, ' ' . $relationship) . ')' :
                    '(' . $this->innerConjunct($value, $map, ' ' . $relationship, $conjunctor) . ')';
                continue;
            }
            $map_key = $this->mapKey();
            if (
                is_int($key) &&
                preg_match('/([a-zA-Z0-9_\.]+)\[(?<operator>\>\=?|\<\=?|\!?\=)\]([a-zA-Z0-9_\.]+)/i', $value, $match)
            ) {
                $stack[] = $this->columnQuote($match[1]) . ' ' . $match['operator'] . ' ' . $this->columnQuote($match[3]);
            } else {
                preg_match('/([a-zA-Z0-9_\.]+)(\[(?<operator>\>\=?|\<\=?|\!|\<\>|\>\<|\!?~|REGEXP)\])?/i', $key, $match);
                $column = $this->columnQuote($match[1]);
                if (isset($match['operator'])) {
                    $operator = $match['operator'];
                    if (in_array($operator, [ '>', '>=', '<', '<=' ])) {
                        $condition = $column . ' ' . $operator . ' ';
                        if (is_numeric($value)) {
                            $condition .= $map_key;
                            $map[$map_key] = [ $value, PDO::PARAM_INT ];
                        } elseif ($raw = $this->buildRaw($value, $map)) {
                            $condition .= $raw;
                        } else {
                            $condition .= $map_key;
                            $map[$map_key] = [ $value, PDO::PARAM_STR ];
                        }
                        $stack[] = $condition;
                    } elseif ($operator === '!') {
                        switch ($type) {
                            case 'NULL':
                                $stack[] = $column . ' IS NOT NULL';
                                break;
                            case 'array':
                                $placeholders = [];
                                foreach ($value as $index => $item) {
                                    $placeholders[] = $map_key . $index . '_i';
                                    $map[$map_key . $index . '_i'] = $this->typeMap($item, gettype($item));
                                }
                                $stack[] = $column . ' NOT IN (' . implode(', ', $placeholders) . ')';
                                break;
                            case 'object':
                                if ($raw = $this->buildRaw($value, $map)) {
                                    $stack[] = $column . ' != ' . $raw;
                                }
                                break;
                            case 'integer':
                            case 'double':
                            case 'boolean':
                            case 'string':
                                $stack[] = $column . ' != ' . $map_key;
                                $map[$map_key] = $this->typeMap($value, $type);
                                break;
                        }
                    } elseif ($operator === '~' || $operator === '!~') {
                        if ($type !== 'array') {
                            $value = [ $value ];
                        }
                        $connector = ' OR ';
                        $data = array_values($value);
                        if (is_array($data[0])) {
                            if (isset($value['AND']) || isset($value['OR'])) {
                                $connector = ' ' . array_keys($value)[0] . ' ';
                                $value = $data[0];
                            }
                        }
                        $like_clauses = [];
                        foreach ($value as $index => $item) {
                            $item = strval($item);
                            if (!preg_match('/(\[.+\]|_|%.+|.+%)/', $item)) {
                                $item = '%' . $item . '%';
                            }
                            $like_clauses[] = $column . ($operator === '!~' ? ' NOT' : '') . ' LIKE ' . $map_key . 'L' . $index;
                            $map[$map_key . 'L' . $index] = [ $item, PDO::PARAM_STR ];
                        }
                        $stack[] = '(' . implode($connector, $like_clauses) . ')';
                    } elseif ($operator === '<>' || $operator === '><') {
                        if ($type === 'array') {
                            if ($operator === '><') {
                                $column .= ' NOT';
                            }
                            $stack[] = '(' . $column . ' BETWEEN ' . $map_key . 'a AND ' . $map_key . 'b)';
                            $data_type = (is_numeric($value[0]) && is_numeric($value[1])) ? PDO::PARAM_INT : PDO::PARAM_STR;
                            $map[$map_key . 'a'] = [ $value[0], $data_type ];
                            $map[$map_key . 'b'] = [ $value[1], $data_type ];
                        }
                    } elseif ($operator === 'REGEXP') {
                        $stack[] = $column . ' REGEXP ' . $map_key;
                        $map[$map_key] = [ $value, PDO::PARAM_STR ];
                    }
                } else {
                    switch ($type) {
                        case 'NULL':
                            $stack[] = $column . ' IS NULL';
                            break;
                        case 'array':
                            $placeholders = [];
                            foreach ($value as $index => $item) {
                                $placeholders[] = $map_key . $index . '_i';
                                $map[$map_key . $index . '_i'] = $this->typeMap($item, gettype($item));
                            }
                            $stack[] = $column . ' IN (' . implode(', ', $placeholders) . ')';
                            break;
                        case 'object':
                            if ($raw = $this->buildRaw($value, $map)) {
                                $stack[] = $column . ' = ' . $raw;
                            }
                            break;
                        case 'integer':
                        case 'double':
                        case 'boolean':
                        case 'string':
                            $stack[] = $column . ' = ' . $map_key;
                            $map[$map_key] = $this->typeMap($value, $type);
                            break;
                    }
                }
            }
        }
        return implode($conjunctor . ' ', $stack);
    }

    protected function whereClause($where, &$map)
    {
        $where_clause = '';
        if (is_array($where)) {
            $where_keys = array_keys($where);
            $conditions = array_diff_key($where, array_flip(
                [ 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH' ]
            ));
            if (!empty($conditions)) {
                $where_clause = ' WHERE ' . $this->dataImplode($conditions, $map, ' AND');
            }
            if (isset($where['MATCH']) && $this->type === 'mysql') {
                $MATCH = $where['MATCH'];
                if (is_array($MATCH) && isset($MATCH['columns'], $MATCH['keyword'])) {
                    $mode = '';
                    $mode_array = [
                        'natural'       => 'IN NATURAL LANGUAGE MODE',
                        'natural+query' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION',
                        'boolean'       => 'IN BOOLEAN MODE',
                        'query'         => 'WITH QUERY EXPANSION'
                    ];
                    if (isset($MATCH['mode'], $mode_array[$MATCH['mode']])) {
                        $mode = ' ' . $mode_array[$MATCH['mode']];
                    }
                    $columns = implode(', ', array_map([ $this, 'columnQuote' ], $MATCH['columns']));
                    $map_key = $this->mapKey();
                    $map[$map_key] = [ $MATCH['keyword'], PDO::PARAM_STR ];
                    $where_clause .= ($where_clause !== '' ? ' AND ' : ' WHERE') . ' MATCH (' . $columns . ') AGAINST (' . $map_key . $mode . ')';
                }
            }
            if (isset($where['GROUP'])) {
                $GROUP = $where['GROUP'];
                if (is_array($GROUP)) {
                    $stack = [];
                    foreach ($GROUP as $column => $value) {
                        $stack[] = $this->columnQuote($value);
                    }
                    $where_clause .= ' GROUP BY ' . implode(',', $stack);
                } elseif ($raw = $this->buildRaw($GROUP, $map)) {
                    $where_clause .= ' GROUP BY ' . $raw;
                } else {
                    $where_clause .= ' GROUP BY ' . $this->columnQuote($GROUP);
                }
                if (isset($where['HAVING'])) {
                    if ($raw = $this->buildRaw($where['HAVING'], $map)) {
                        $where_clause .= ' HAVING ' . $raw;
                    } else {
                        $where_clause .= ' HAVING ' . $this->dataImplode($where['HAVING'], $map, ' AND');
                    }
                }
            }
            if (isset($where['ORDER'])) {
                $ORDER = $where['ORDER'];
                if (is_array($ORDER)) {
                    $stack = [];
                    foreach ($ORDER as $column => $value) {
                        if (is_array($value)) {
                            $stack[] = 'FIELD(' . $this->columnQuote($column) . ', ' . $this->arrayQuote($value) . ')';
                        } elseif ($value === 'ASC' || $value === 'DESC') {
                            $stack[] = $this->columnQuote($column) . ' ' . $value;
                        } elseif (is_int($column)) {
                            $stack[] = $this->columnQuote($value);
                        }
                    }
                    $where_clause .= ' ORDER BY ' . implode(',', $stack);
                } elseif ($raw = $this->buildRaw($ORDER, $map)) {
                    $where_clause .= ' ORDER BY ' . $raw;
                } else {
                    $where_clause .= ' ORDER BY ' . $this->columnQuote($ORDER);
                }
                if (
                    isset($where['LIMIT']) &&
                    in_array($this->type, [ 'oracle', 'mssql' ])
                ) {
                    $LIMIT = $where['LIMIT'];
                    if (is_numeric($LIMIT)) {
                        $LIMIT = [ 0, $LIMIT ];
                    }
                    if (
                        is_array($LIMIT) &&
                        is_numeric($LIMIT[0]) &&
                        is_numeric($LIMIT[1])
                    ) {
                        $where_clause .= ' OFFSET ' . $LIMIT[0] . ' ROWS FETCH NEXT ' . $LIMIT[1] . ' ROWS ONLY';
                    }
                }
            }
            if (isset($where['LIMIT']) && !in_array($this->type, [ 'oracle', 'mssql' ])) {
                $LIMIT = $where['LIMIT'];
                if (is_numeric($LIMIT)) {
                    $where_clause .= ' LIMIT ' . $LIMIT;
                } elseif (
                    is_array($LIMIT) &&
                    is_numeric($LIMIT[0]) &&
                    is_numeric($LIMIT[1])
                ) {
                    $where_clause .= ' LIMIT ' . $LIMIT[1] . ' OFFSET ' . $LIMIT[0];
                }
            }
        } elseif ($raw = $this->buildRaw($where, $map)) {
            $where_clause .= ' ' . $raw;
        }
        return $where_clause;
    }

    protected function selectContext($table, &$map, $join, &$columns = null, $where = null, $column_fn = null)
    {
        preg_match('/(?<table>[a-zA-Z0-9_]+)\s*\((?<alias>[a-zA-Z0-9_]+)\)/i', $table, $table_match);
        if (isset($table_match['table'], $table_match['alias'])) {
            $table = $this->tableQuote($table_match['table']);
            $table_query = $table . ' AS ' . $this->tableQuote($table_match['alias']);
        } else {
            $table = $this->tableQuote($table);
            $table_query = $table;
        }
        $join_key = is_array($join) ? array_keys($join) : null;
        if (
            isset($join_key[0]) &&
            strpos($join_key[0], '[') === 0
        ) {
            $table_join = [];
            $join_array = [
                '>'  => 'LEFT',
                '<'  => 'RIGHT',
                '<>' => 'FULL',
                '><' => 'INNER'
            ];
            foreach ($join as $sub_table => $relation) {
                preg_match('/(\[(?<join>\<\>?|\>\<?)\])?(?<table>[a-zA-Z0-9_]+)\s?(\((?<alias>[a-zA-Z0-9_]+)\))?/', $sub_table, $match);
                if ($match['join'] !== '' && $match['table'] !== '') {
                    if (is_string($relation)) {
                        $relation = 'USING ("' . $relation . '")';
                    }
                    if (is_array($relation)) {
                        // For ['column1', 'column2']
                        if (isset($relation[0])) {
                            $relation = 'USING ("' . implode('", "', $relation) . '")';
                        } else {
                            $joins = [];
                            foreach ($relation as $key => $value) {
                                $joins[] = (
                                    strpos($key, '.') > 0 ?
                                        // For ['tableB.column' => 'column']
                                        $this->columnQuote($key) :
                                        // For ['column1' => 'column2']
                                        $table . '."' . $key . '"'
                                    ) .
                                    ' = ' .
                                    $this->tableQuote(isset($match['alias']) ? $match['alias'] : $match['table']) . '."' . $value . '"';
                            }
                            $relation = 'ON ' . implode(' AND ', $joins);
                        }
                    }
                    $table_name = $this->tableQuote($match['table']) . ' ';
                    if (isset($match['alias'])) {
                        $table_name .= 'AS ' . $this->tableQuote($match['alias']) . ' ';
                    }
                    $table_join[] = $join_array[$match['join']] . ' JOIN ' . $table_name . $relation;
                }
            }
            $table_query .= ' ' . implode(' ', $table_join);
        } else {
            if (is_null($columns)) {
                if (
                    !is_null($where) ||
                    (is_array($join) && isset($column_fn))
                ) {
                    $where = $join;
                    $columns = null;
                } else {
                    $where = null;
                    $columns = $join;
                }
            } else {
                $where = $columns;
                $columns = $join;
            }
        }
        if (isset($column_fn)) {
            if ($column_fn === 1) {
                $column = '1';
                if (is_null($where)) {
                    $where = $columns;
                }
            } else {
                if (empty($columns) || $this->isRaw($columns)) {
                    $columns = '*';
                    $where = $join;
                }
                $column = $column_fn . '(' . $this->columnPush($columns, $map) . ')';
            }
        } else {
            $column = $this->columnPush($columns, $map);
        }
        return 'SELECT ' . $column . ' FROM ' . $table_query . $this->whereClause($where, $map);
    }

    protected function columnMap($columns, &$stack)
    {
        if ($columns === '*') {
            return $stack;
        }
        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                preg_match('/([a-zA-Z0-9_]+\.)?(?<column>[a-zA-Z0-9_]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/i', $value, $key_match);
                $column_key = !empty($key_match['alias']) ?
                    $key_match['alias'] :
                    $key_match['column'];
                if (isset($key_match['type'])) {
                    $stack[$value] = [ $column_key, $key_match['type'] ];
                } else {
                    $stack[$value] = [ $column_key, 'String' ];
                }
            } elseif ($this->isRaw($value)) {
                preg_match('/([a-zA-Z0-9_]+\.)?(?<column>[a-zA-Z0-9_]+)(\s*\[(?<type>(String|Bool|Int|Number))\])?/i', $key, $key_match);
                $column_key = $key_match['column'];
                if (isset($key_match['type'])) {
                    $stack[$key] = [ $column_key, $key_match['type'] ];
                } else {
                    $stack[$key] = [ $column_key, 'String' ];
                }
            } elseif (!is_int($key) && is_array($value)) {
                $this->columnMap($value, $stack);
            }
        }
        return $stack;
    }

    protected function dataMap($data, $columns, $column_map, &$stack)
    {
        foreach ($columns as $key => $value) {
            $isRaw = $this->isRaw($value);
            if (is_int($key) || $isRaw) {
                $map = $column_map[$isRaw ? $key : $value];
                $column_key = $map[0];
                $result = $data[$column_key];
                if (isset($map[1])) {
                    if ($isRaw && in_array($map[1], [ 'Object', 'JSON' ])) {
                        continue;
                    }
                    if (is_null($result)) {
                        $stack[$column_key] = null;
                        continue;
                    }
                    switch ($map[1]) {
                        case 'Number':
                            $stack[$column_key] = (double)$result;
                            break;
                        case 'Int':
                            $stack[$column_key] = (int)$result;
                            break;
                        case 'Bool':
                            $stack[$column_key] = (bool)$result;
                            break;
                        case 'Object':
                            $stack[$column_key] = unserialize($result);
                            break;
                        case 'JSON':
                            $stack[$column_key] = json_decode($result, true);
                            break;
                        case 'String':
                            $stack[$column_key] = $result;
                            break;
                    }
                } else {
                    $stack[$column_key] = $result;
                }
            } else {
                $current_stack = [];
                $this->dataMap($data, $value, $column_map, $current_stack);
                $stack[$key] = $current_stack;
            }
        }
    }

    public function select($table, $join, $columns = null, $where = null)
    {
        $map = [];
        $stack = [];
        $column_map = [];
        $index = 0;
        $column = $where === null ? $join : $columns;
        $is_single = (is_string($column) && $column !== '*');
        $query = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);
        $this->columnMap($columns, $column_map);
        if (!$query) {
            return false;
        }
        if ($columns === '*') {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($is_single) {
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $current_stack = [];
            $this->dataMap($data, $columns, $column_map, $current_stack);
            $stack[$index] = $current_stack;
            $index++;
        }
        return $stack;
    }

    public function insert($table, $datas)
    {
        $stack = [];
        $columns = [];
        $fields = [];
        $map = [];
        if (!isset($datas[0])) {
            $datas = [ $datas ];
        }
        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                $columns[] = $key;
            }
        }
        $columns = array_unique($columns);
        foreach ($datas as $data) {
            $values = [];
            foreach ($columns as $key) {
                if ($raw = $this->buildRaw($data[$key], $map)) {
                    $values[] = $raw;
                    continue;
                }
                $map_key = $this->mapKey();
                $values[] = $map_key;
                if (!isset($data[$key])) {
                    $map[$map_key] = [ null, PDO::PARAM_NULL ];
                } else {
                    $value = $data[$key];
                    $type = gettype($value);
                    switch ($type) {
                        case 'array':
                            $map[$map_key] = [
                                strpos($key, '[JSON]') === strlen($key) - 6 ?
                                    json_encode($value) :
                                    serialize($value),
                                PDO::PARAM_STR
                            ];
                            break;
                        case 'object':
                            $value = serialize($value);
                        case 'NULL':
                        case 'resource':
                        case 'boolean':
                        case 'integer':
                        case 'double':
                        case 'string':
                            $map[$map_key] = $this->typeMap($value, $type);
                            break;
                    }
                }
            }
            $stack[] = '(' . implode(', ', $values) . ')';
        }
        foreach ($columns as $key) {
            $fields[] = $this->columnQuote(preg_replace("/(\s*\[JSON\]$)/i", '', $key));
        }
        return $this->exec('INSERT INTO ' . $this->tableQuote($table) . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $stack), $map);
    }

    public function update($table, $data, $where = null)
    {
        $fields = [];
        $map = [];
        foreach ($data as $key => $value) {
            $column = $this->columnQuote(preg_replace("/(\s*\[(JSON|\+|\-|\*|\/)\]$)/i", '', $key));
            if ($raw = $this->buildRaw($value, $map)) {
                $fields[] = $column . ' = ' . $raw;
                continue;
            }
            $map_key = $this->mapKey();
            preg_match('/(?<column>[a-zA-Z0-9_]+)(\[(?<operator>\+|\-|\*|\/)\])?/i', $key, $match);
            if (isset($match['operator'])) {
                if (is_numeric($value)) {
                    $fields[] = $column . ' = ' . $column . ' ' . $match['operator'] . ' ' . $value;
                }
            } else {
                $fields[] = $column . ' = ' . $map_key;
                $type = gettype($value);
                switch ($type) {
                    case 'array':
                        $map[$map_key] = [
                            strpos($key, '[JSON]') === strlen($key) - 6 ?
                                json_encode($value) :
                                serialize($value),
                            PDO::PARAM_STR
                        ];
                        break;
                    case 'object':
                        $value = serialize($value);
                    case 'NULL':
                    case 'resource':
                    case 'boolean':
                    case 'integer':
                    case 'double':
                    case 'string':
                        $map[$map_key] = $this->typeMap($value, $type);
                        break;
                }
            }
        }
        return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $fields) . $this->whereClause($where, $map), $map);
    }

    public function delete($table, $where)
    {
        $map = [];
        return $this->exec('DELETE FROM ' . $this->tableQuote($table) . $this->whereClause($where, $map), $map);
    }

    public function replace($table, $columns, $where = null)
    {
        if (!is_array($columns) || empty($columns)) {
            return false;
        }
        $map = [];
        $stack = [];
        foreach ($columns as $column => $replacements) {
            if (is_array($replacements)) {
                foreach ($replacements as $old => $new) {
                    $map_key = $this->mapKey();
                    $stack[] = $this->columnQuote($column) . ' = REPLACE(' . $this->columnQuote($column) . ', ' . $map_key . 'a, ' . $map_key . 'b)';
                    $map[$map_key . 'a'] = [ $old, PDO::PARAM_STR ];
                    $map[$map_key . 'b'] = [ $new, PDO::PARAM_STR ];
                }
            }
        }
        if (!empty($stack)) {
            return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $stack) . $this->whereClause($where, $map), $map);
        }
        return false;
    }

    public function get($table, $join = null, $columns = null, $where = null)
    {
        $map = [];
        $stack = [];
        $column_map = [];
        if ($where === null) {
            $column = $join;
            unset($columns['LIMIT']);
        } else {
            $column = $columns;
            unset($where['LIMIT']);
        }
        $is_single = (is_string($column) && $column !== '*');
        $query = $this->exec($this->selectContext($table, $map, $join, $columns, $where) . ' LIMIT 1', $map);
        if ($query) {
            $data = $query->fetchAll(PDO::FETCH_ASSOC);
            if (isset($data[0])) {
                if ($column === '*') {
                    return $data[0];
                }
                $this->columnMap($columns, $column_map);
                $this->dataMap($data[0], $columns, $column_map, $stack);
                if ($is_single) {
                    return $stack[$column_map[$column][0]];
                }
                return $stack;
            }
        }
    }

    public function has($table, $join, $where = null)
    {
        $map = [];
        $column = null;
        $query = $this->exec('SELECT EXISTS(' . $this->selectContext($table, $map, $join, $column, $where, 1) . ')', $map);
        if ($query) {
            $result = $query->fetchColumn();
            return $result === '1' || $result === true;
        }
        return false;
    }

    public function rand($table, $join = null, $columns = null, $where = null)
    {
        $type = $this->type;
        $order = 'RANDOM()';
        if ($type === 'mysql') {
            $order = 'RAND()';
        } elseif ($type === 'mssql') {
            $order = 'NEWID()';
        }
        $order_raw = $this->raw($order);
        if ($where === null) {
            if ($columns === null) {
                $columns = [
                    'ORDER' => $order_raw
                ];
            } else {
                $column = $join;
                unset($columns['ORDER']);
                $columns['ORDER'] = $order_raw;
            }
        } else {
            unset($where['ORDER']);
            $where['ORDER'] = $order_raw;
        }
        return $this->select($table, $join, $columns, $where);
    }

    private function aggregate($type, $table, $join = null, $column = null, $where = null)
    {
        $map = [];
        $query = $this->exec($this->selectContext($table, $map, $join, $column, $where, strtoupper($type)), $map);
        if ($query) {
            $number = $query->fetchColumn();
            return is_numeric($number) ? $number + 0 : $number;
        }
        return false;
    }

    public function count($table, $join = null, $column = null, $where = null)
    {
        return $this->aggregate('count', $table, $join, $column, $where);
    }

    public function avg($table, $join, $column = null, $where = null)
    {
        return $this->aggregate('avg', $table, $join, $column, $where);
    }

    public function max($table, $join, $column = null, $where = null)
    {
        return $this->aggregate('max', $table, $join, $column, $where);
    }

    public function min($table, $join, $column = null, $where = null)
    {
        return $this->aggregate('min', $table, $join, $column, $where);
    }

    public function sum($table, $join, $column = null, $where = null)
    {
        return $this->aggregate('sum', $table, $join, $column, $where);
    }

    public function action($actions)
    {
        if (is_callable($actions)) {
            $this->pdo->beginTransaction();
            try {
                $result = $actions($this);
                if ($result === false) {
                    $this->pdo->rollBack();
                } else {
                    $this->pdo->commit();
                }
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }
            return $result;
        }
        return false;
    }

    public function id()
    {
        $type = $this->type;
        if ($type === 'oracle') {
            return 0;
        } elseif ($type === 'mssql') {
            return $this->pdo->query('SELECT SCOPE_IDENTITY()')->fetchColumn();
        } elseif ($type === 'pgsql') {
            return $this->pdo->query('SELECT LASTVAL()')->fetchColumn();
        }
        return $this->pdo->lastInsertId();
    }

    public function debug()
    {
        $this->debug_mode = true;
        return $this;
    }

    public function error()
    {
        return $this->statement ? $this->statement->errorInfo() : null;
    }

    public function last()
    {
        $log = end($this->logs);
        return $this->generate($log[0], $log[1]);
    }

    public function log()
    {
        return array_map(function ($log) {
            return $this->generate($log[0], $log[1]);
        }
            ,
            $this->logs
        );
    }

    public function info()
    {
        $output = [
            'server'     => 'SERVER_INFO',
            'driver'     => 'DRIVER_NAME',
            'client'     => 'CLIENT_VERSION',
            'version'    => 'SERVER_VERSION',
            'connection' => 'CONNECTION_STATUS'
        ];
        foreach ($output as $key => $value) {
            $output[$key] = @$this->pdo->getAttribute(constant('PDO::ATTR_' . $value));
        }
        $output['dsn'] = $this->dsn;
        return $output;
    }
}