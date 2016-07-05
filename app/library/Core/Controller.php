<?php
namespace Core;

class Controller extends \Yaf\Controller_Abstract
{

    protected $yafAutoRender = false;

    private $view;

    public function get($name, $default = null)
    {
        $val = $this->getRequest()->getParam($name);
        if ($val != null) {
            return $val;
        }
        if (isset($_POST[$name])) {
            return $_POST[$name];
        }
        if (isset($_GET[$name])) {
            return $_GET[$name];
        }

        return $default;
    }

    public function render($tpl, array $vars = [])
    {
        $view = $this->getView();
        $view->render($tpl, $vars);
        $this->getResponse()->setBody($view->getContent());
    }

    /**
     *
     * @return Template
     */
    public function getView()
    {
        if (empty($this->view)) {
            $name = $this->getRequest()->getModuleName();
            if ($name === 'Index') {
                $path = APP_PATH . '/app/views';
            } else {
                $path = APP_PATH . '/app/modules/' . $name . '/views';
            }
            $this->view = new Template();
            $this->view->setScriptPath($path);
        }

        return $this->view;
    }

    public function renderJson($data = null, $code = 0, $msg = '')
    {
        $json = [
            'code' => $code,
            'message' => $msg,
            'data' => $data ? $data : null,
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
        return trim($_SERVER['HTTP_REFERER']);
    }
}
