<?php
namespace Core;

use Yaf;
use Yaf\Application;
use Yaf\Registry;


abstract class Restful extends \Yaf\Controller_Abstract
{
    protected abstract function get();

    protected abstract function post();

    protected abstract function put();

    protected abstract function delete();

    protected abstract function options();

    protected abstract function head();

    protected abstract function trace();

    protected abstract function connect();

    protected $yafAutoRender = false;

    public function indexAction()
    {
        $requestMethod = strtolower($this->getRequest()->getMethod());
        $requestApiVersion = isset($_SERVER['HTTP_JIAOCHENG_API_VERSION']) ? $_SERVER['HTTP_JIAOCHENG_API_VERSION'] : null;
        $serverApiVersion = Registry::get('conf')->version;
        $requestAction = $requestMethod . $requestApiVersion;

        if (!method_exists($this, $requestAction) || $requestApiVersion != $serverApiVersion) {
            $this->$requestAction();
        } else {
            $this->$requestMethod();
        }
    }

    public function getParam($key, $default = null)
    {
        $val = $this->getRequest()->getParam($key);
        return $val ? $val : $default;
    }

    public function getRestfulParam($key = null, $default = null)
    {
        $restfulParam = json_decode(file_get_contents('php://input'), true);
        if ($key) {
            return $restfulParam[$key] ? $restfulParam[$key] : $default;
        } else {
            return $restfulParam ? $restfulParam : $default;
        }
    }

    public function getHttpGetParam($key, $default = null)
    {
        return $_GET[$key] ? $_GET[$key] : $default;
    }

    public function getHttpPostParam($key, $default = null)
    {
        return $_POST[$key] ? $_POST[$key] : $default;
    }

    public function renderJson($dict = null, $list = null, $code = 0, $msg = 'ok')
    {
        $json = [
            'code' => $code,
            'message' => $msg,
            'dict' => $dict ? (object)$dict : (object)[],
            'list' => $list ? (array)$list : []
        ];

        if (!empty($_GET['debug'])) {
            header('Content-Type: text/html; charset=utf-8');
            echo "<pre>";
            print_r($json);
            return;
        }

        if ($code > 0) {
            header('HTTP/1.1 500 Server Error');
        }

        header('Content-Type: application/json; charset=utf-8');
        $this->getResponse()->setBody(json_encode($json, JSON_UNESCAPED_UNICODE));
    }

    public function createUrl($route, $query)
    {
        return Yaf\Application::app()
            ->getDispatcher()
            ->getRouter()
            ->getCurrentRoute()
            ->assemble($route, $query);
    }

    public function referer()
    {
        return isset($_SERVER['HTTP_REFERER']) ? trim($_SERVER['HTTP_REFERER']) : null;
    }
}
