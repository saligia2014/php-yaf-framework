<?php

use Yaf\Request_Abstract as Request;
use Yaf\Response_Abstract as Response;
use Yaf\Plugin_Abstract;
use Yaf\Application;
use Yaf\Registry;
use Yaf\Exception;
use Core\Stmt;


class MainPlugin extends Plugin_Abstract
{

    public function routerStartup(Request $request, Response $response)
    {

    }

    public function routerShutdown(Request $request, Response $response)
    {

    }

    public function dispatchLoopStartup(Request $request, Response $response)
    {
        $name = $request->getModuleName();
        if (strncmp($name, 'Cli', 3) === 0 && php_sapi_name() != 'cli') {
            header('HTTP/1.1: 403 Forbidden', true, 403);
            throw new Exception('Forbidden fpm request in Cli mode', 1);
        }
    }

    public function preDispatch(Request $request, Response $response)
    {

    }

    public function postDispatch(Request $request, Response $response)
    {

    }

    public function dispatchLoopShutdown(Request $request, Response $response)
    {
        if (Application::app()->environ() == ENV_DEV) {
            $this->dumpSql();
        }
    }

    private function dumpSql()
    {
        $conf = Registry::get('conf')->log->db;
        if ($conf->log_file) {
            $count = count(Stmt::getLogs());
            if ($count > 0) {
                $uri = Application::app()
                    ->getDispatcher()
                    ->getRequest()
                    ->getRequestUri();
                $fp = fopen($conf->log_file, 'a');
                fwrite($fp, 'URI: ' . $uri . ", $count sql\n\n");
                foreach (Stmt::getLogs() as $log) {
                    fwrite($fp, '    ' . $log['sql'] . "\n\n");
                    if (count($log['params'])) {
                        fwrite($fp, '    ');
                        foreach ($log['params'] as $i => $val) {
                            fwrite($fp, $val . ' ');
                            if (($i + 1) % 10 == 0) {
                                fwrite($fp, "\n    ");
                            }
                            if ($i >= $conf->max_params_size) {
                                break;
                            }
                        }
                    }

                    fwrite($fp, "\n\n\n");
                }

                fwrite($fp, 'URI: ' . $uri . ", $count sql\n\n");
                fclose($fp);
            }
        }
    }
}