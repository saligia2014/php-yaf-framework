<?php

namespace Core;

use Yaf\View_Interface;
use Yaf\Registry;
use Yaf\Exception;

class Template implements View_Interface
{

    const ACTION_BLOCK = 0;

    private $ext;

    private $dir;

    private $vars = array();

    private $content;

    private $blocks = array();

    private $child = true;

    private $layout = 'layouts/main';

    private $conf;

    public function __construct($ext = 'phtml')
    {
        $this->ext = $ext;
        $this->vars['t'] = $this;

        $this->conf = Registry::get('conf')->static;
    }

    public function getScriptPath()
    {
        return $this->dir;
    }

    public function setScriptPath($dir)
    {
        $this->dir = $dir;
    }

    public function getContent()
    {
        if ($this->layout) {
            $this->child = false;
            $this->render($this->layout);
        }
        return $this->content;
    }

    public function assign($name, $value = '')
    {
        $this->vars[$name] = $value;
    }

    public function display($tpl, $vars = array())
    {
        $this->render($tpl, $vars);
        echo $this->content;
    }

    public function render($tpl, $vars = array())
    {
        $this->vars = array_merge($this->vars, $vars);
        $this->renderTpl($tpl);

        if ($this->layout) {
            $this->child = false;
            $this->renderTpl($this->layout);
        }
    }

    public function contain($tpl, $vars = array())
    {
        extract($vars);
        require $this->dir . '/' . $tpl . '.' . $this->ext;
    }

    private function renderTpl($tpl)
    {
        ob_start();
        extract($this->vars);
        require $this->dir . '/' . $tpl . '.' . $this->ext;
        if (count($this->blockNames) > 0) {
            throw new Exception('some block not end', 1);
        }
        $this->content = ob_get_contents();
        ob_end_clean();
    }

    private $blockNames = array();

    private $actions = array();

    public function block($name)
    {
        array_push($this->blockNames, $name);
        array_push($this->actions, self::ACTION_BLOCK);
        ob_start();
    }

    public function endBlock()
    {
        $name = array_pop($this->blockNames);
        if ($name) {
            if ($this->child) {
                $content = ob_get_contents();
                ob_end_clean();
                $this->blocks[$name] = $content;
            } else if (isset($this->blocks[$name])) {
                ob_end_clean();
                echo $this->blocks[$name];
            } else {
                ob_end_flush();
            }
        } else {
            throw new Exception('no starting block', 1);
        }
    }

    public function end()
    {
        switch (array_pop($this->actions)) {
            case self::ACTION_BLOCK:
                $this->endBlock();
                break;
            default :
                throw new Exception('missing starting block', 1);
        }
    }

    public function css($src)
    {
        return '<link href="' . $src . '" rel="stylesheet">';
    }

    public function js($src)
    {
        return '<script src="' . $src . '"></script>';
    }

    public function text($text)
    {
        return htmlspecialchars($text);
    }

    public function string($str)
    {
        return addslashes($str);
    }
}
