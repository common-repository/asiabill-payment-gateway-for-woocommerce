<?php
// +---------------------------------------------------------------------------------
// | Asiabill PHPSDK
// +---------------------------------------------------------------------------------
// | 集成了AsiaBill的payment和openApi接口的组件，通过传递指定请求类型和对应的参数即可完成接口请求
// +---------------------------------------------------------------------------------
// | api文档：https://asiabill.gitbook.io/api-explorer/
// +---------------------------------------------------------------------------------
// | github开源项目：https://github.com/Asiabill/asiabill_php_sdk
// +---------------------------------------------------------------------------------

namespace Asiabill\Classes;

if (phpversion() < '7.0.0') {
    exit('PHP 7.0.0 or higher is required');
}

include_once 'AsiabillConfig.php';
include_once 'AsiabillFacade.php';
include_once 'AsiabillHttp.php';
include_once 'AsiabillLogger.php';

use \Exception;

/**
 * @see AsiabillFacade
 * @method static string isMobile() 是否是手机
 * @method static string clientIP() 客户端IP
 * @method static array getWebhookData() 获取异步消息体
 * @method static string requestTime() 请求时间
 * @method static string uniqueId($millisecond) 唯一识别码
 * @method static string signInfo(array $data, string $sign_key) 计算字符串的hash值
 *
 * @see AsiabillIntegration
 * @method config(string $name = null, $default = null);
 * @method log(string $message = null);
 */
class AsiabillIntegration
{
    const VERSION = '2.0';

    protected $_mode;
    protected $_gateway_no;
    protected $_sign_key;
    protected $_parameters;
    protected $_config;
    protected $_logger;

    public $error = '';

    /**
     * 初始化方法
     * @param $mode // test or live
     * @param $gateway_no
     * @param $sign_key
     * @throws Exception
     */
    function __construct($mode, $gateway_no, $sign_key)
    {
        if (empty($mode) || empty($gateway_no) || empty($sign_key)) {
            throw new Exception('Initialization error');
        }

        if (!in_array($mode, ['test', 'live'])) {
            throw new Exception('The "mode" must be "test" or "live"');
        }

        $this->_mode = $mode;
        $this->_gateway_no = $gateway_no;
        $this->_sign_key = $sign_key;

        $millisecond = self::requestTime();
        $this->_parameters = [
            'header' => [
                'gateway-no'   => $this->_gateway_no,
                'request-id'   => self::uniqueId($millisecond),
                'request-time' => $millisecond,
                'sign-info'    => ''
            ],
        ];

    }

    /**
     * 获取js sdk脚本地址
     * @return string
     */
    public function getJsScript(): string
    {
        return $this->config('payment.' . $this->_mode) . '/static/v3/js/AsiabillPayment.min.js?v=' . self::VERSION;
    }

    /**
     * 开启日志
     * @param bool $bool
     * @return $this
     */
    public function startLogger(bool $bool = true): AsiabillIntegration
    {
        $this->config('logger.start', $bool);
        return $this;
    }

    /**
     * 绑定自定义日志类
     * @see \Asiabill\Classes\AsiabillLogger
     * @param $logger // 自定义日志类
     * @param array $args // 日志初始化参数
     *              使用默认日志类配置参数
     *              [
     *                  'dir'  => $dir, // 自定义日志目录
     *                  'file' => $file // 自定义日志文件
     *              ]
     * @param string $method // 日志方法
     * @return void
     * @throws \ReflectionException
     */
    public function binLogger($logger = null, array $args = [], string $method = '')
    {
        if (!empty($method)) {
            $this->config('logger.method', $method);
        }

        if (!empty($args)) {
            $this->config('logger', $args);
        }

        $class = empty($logger) ? $this->config('logger.class') : $logger;
        if ($class == 'Asiabill\Classes\AsiabillLogger') {
            $args = [
                'dir'  => $this->config('logger.dir'),
                'file' => $this->config('logger.file'),
            ];
        }

        if (empty($this->_logger)) {
            $reflector = new \ReflectionClass($class);
            $this->_logger = $reflector->newInstanceArgs($args);
        }
    }

    /**
     * 发送请求
     * @param string $uri 请求接口
     * @param array $data 请求数据
     *                    [
     *                        'path'  => [],
     *                        'query' => [],
     *                        'body'  => []
     *                    ]
     * @return mixed
     * @throws Exception
     */
    public function request(string $uri, array $data = array())
    {
        if (method_exists($this, $uri)) {
            return $this->$uri($data);
        }
        return $this->requestCommon($uri, $data);
    }

    public function customers(array $data)
    {
        $uri = 'customers';

        if (isset($data['body'])) {
            $this->_parameters['body'] = $data['body'];
            return $this->handle($uri);
        }
        else {
            if (isset($data['path'])) {
                $this->_parameters['path'] = $data['path'];
                $uri .= '/' . $data['path']['customerId'];
                if (isset($data['delete']) && $data['delete'] === true) {
                    return $this->handle($uri, 'DELETE');
                }
            }
            else {
                if (isset($data['query'])) {
                    $this->_parameters['query'] = $data['query'];
                    $uri .= '?' . http_build_query($this->_parameters['query']);
                }
            }
            return $this->handle($uri, 'GET');
        }
    }

    public function sessionToken()
    {
        $result = $this->handle('sessionToken');
        if ($result['code'] == '00000') {
            return $result['data']['sessionToken'];
        }
        throw new Exception('Get token err: ' . $result['message']);
    }

    private function requestCommon($uri, $data)
    {
        $method = 'POST';

        if (is_array($data)) {
            if (isset($data['path'])) {
                $this->_parameters['path'] = $data['path'];
            }

            if (isset($data['query'])) {
                $this->_parameters['query'] = $data['query'];
            }

            if (isset($data['body'])) {
                $this->_parameters['body'] = $data['body'];
            }
        }

        if (empty($this->_parameters['body'])) {
            $method = 'GET';
        }

        return $this->handle($uri, $method);
    }

    private function handle($uri, $method = 'POST')
    {
        $this->_parameters['header']['sign-info'] = self::signInfo($this->_parameters, $this->_sign_key);

        $api = $this->api($uri);

        $this->log('request-api : ' . $api);
        $this->log('parameters : ' . json_encode($this->_parameters, JSON_UNESCAPED_UNICODE));

        $asiabillHttp = new AsiabillHttp($api);

        if ($asiabillHttp->request($this->_parameters, $method)) {   // 请求成功，返回响应体
            $this->log('response : ' . $asiabillHttp->getResponseInfo('Response'));
            return $asiabillHttp->getResponseToArr();
        }
        else // 请求失败，报错提示
        {
            $info = $asiabillHttp->getResponseInfo();
            throw new Exception('Request "' . $info['Request URL'] . '" to status code : "' . $info['Status code'] . '"');
        }
    }

    private function api($uri): string
    {
        $type = key_exists($uri, $this->config('uri.openapi')) ? 'openapi' : 'payment';

        $route = $this->config('uri.' . $type . '.' . $uri);

        if (isset($this->_parameters['path'])) {
            foreach ($this->_parameters['path'] as $key => $val) {
                $route = str_replace('{' . $key . '}', $val, $uri);
            }
        }

        if (isset($this->_parameters['query'])) {
            $route .= '?' . http_build_query($this->_parameters['query']);
        }

        return $this->config($type . '.' . $this->_mode) . $this->config('version') . $route;
    }

    /**
     * 把支付结果转成url参数
     * @param array $result
     * @param int $flags
     * @return string
     */
    public function buildQuery(array $result, int $flags = 0): string
    {
        $data = [
            'query' => [
                'merNo'     => substr($this->_gateway_no, 0, 5),
                'gatewayNo' => $this->_gateway_no,
                'code'      => $result['code'],
                'message'   => $result['message'],
            ]
        ];

        $arr = ['orderNo', 'orderAmount', 'tradeNo', 'orderCurrency', 'orderInfo', 'orderStatus', 'signInfo'];

        foreach ($arr as $value) {
            if (isset($result['data'][$value])) {
                $data['query'][$value] = $result['data'][$value];
            }
            else {
                $data['query'][$value] = '';
            }
        }

        $data['query']['signInfo'] = strtoupper(self::signInfo($data, $this->_sign_key));

        if ($flags) {
            return $data['query'];
        }

        return http_build_query($data['query']);
    }

    /**
     * 验证消息
     * @return bool
     */
    public function verification(): bool
    {
        // returnUrl 接收验证签名
        if (!empty($_GET) && isset($_GET['tradeNo'])) {
            $data = [];
            $signInfo = $_GET['signInfo'];

            $data['query'] = [
                'orderNo'       => $_GET['orderNo'],
                'orderAmount'   => $_GET['orderAmount'],
                'code'          => $_GET['code'],
                'merNo'         => $_GET['merNo'],
                'gatewayNo'     => $_GET['gatewayNo'],
                'tradeNo'       => $_GET['tradeNo'],
                'orderCurrency' => $_GET['orderCurrency'],
                'orderInfo'     => $_GET['orderInfo'],
                'orderStatus'   => $_GET['orderStatus'],
                'maskCardNo'    => $_GET['maskCardNo'] ?? '',
                'message'       => $_GET['message'],
            ];

            $this->log('browser : ' . json_encode($data, JSON_UNESCAPED_UNICODE));

            if (@$signInfo == strtoupper(self::signInfo($data, $this->_sign_key))) {
                return true;
            }

            $this->error = 'Invalid SignInfo';
        }
        // webhook 接收验证签名
        elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $request_header = [];
            foreach ($_SERVER as $key => $value) {
                if (substr($key, 0, 5) == 'HTTP_') {
                    $request_header[strtolower(substr($key, 5))] = $value;
                }
            }

            if (
                empty($request_header['request_time']) ||
                empty($request_header['request_id'])
            ) {
                $this->error = 'Missing Request Header Information';
                return false;
            }

            $cur_time = self::requestTime();
            if ($cur_time - $request_header['request_time'] > $this->config('webhook.timeout')) {
                $this->error = 'Request Time Exceeds 10 Minutes';
                return false;
            }

            $data = [
                'header' => [
                    'gateway-no'   => $this->_gateway_no,
                    'request-time' => $request_header['request_time'],
                    'request-id'   => $request_header['request_id'],
                    'version'      => $request_header['version']
                ],
                'body'   => self::getWebhookData()
            ];


            $data['header']['sign-info'] = @$request_header['sign_info'];
            $this->log('request_header : ' . json_encode($request_header, JSON_UNESCAPED_UNICODE));
            $this->log('webhook : ' . json_encode($data, JSON_UNESCAPED_UNICODE));


            if (@$request_header['sign_info'] == strtoupper(self::signInfo($data, $this->_sign_key))) {
                return true;
            }

            $this->error = 'Invalid SignInfo';
        }
        else {
            $this->error = 'Parameter Empty';
        }

        return false;
    }

    /**
     * 响应处理结果
     * @return void
     */
    public function response()
    {
        echo empty($this->error) ? 'success' : $this->error;
        $this->error = '';
        exit();
    }

    static function __callStatic($name, $arguments)
    {
        if (method_exists(AsiabillFacade::class, $name)) {
            return call_user_func_array(array(AsiabillFacade::class, $name), $arguments);
        }
        throw new Exception('Static method "' . $name . '" does not exist');
    }

    function __call($name, $arguments)
    {
        if (method_exists(AsiabillFacade::class, $name)) {
            return call_user_func_array(array(AsiabillFacade::class, $name), $arguments);
        }

        switch ($name) {
            case 'config':
                if (empty($this->_config)) {
                    $this->_config = new AsiabillConfig();
                    $this->_config->load();
                }
                if (!empty($arguments[1])) {
                    $this->_config->set($arguments[0], $arguments[1]);
                    break;
                }
                return $this->_config->get($arguments[0]??null);
            case 'log':
                if (empty($this->_logger)) {
                    $this->binLogger();
                }
                if ($this->config('logger.start') == true) {
                    call_user_func_array(array($this->_logger, $this->config('logger.method')), $arguments);
                }
                break;
            case 'openapi':
            case 'payment':
            case 'addRequest':
                return $this;
            default:
                throw new Exception('Method "' . $name . '" does not exist');
        }

    }
}
