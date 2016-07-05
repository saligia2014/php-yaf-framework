<?php

namespace Core;

class Pager
{

    private $page;
    private $pageSize = 50;
    private $buttonRange = 10;
    private $totalNum;
    private $pageNum;
    private $startPage;
    private $endPage;
    private $params = [];
    private $hasFirstLast = true;
    private $urlPath;

    public function __construct($page, $totalNum)
    {
        $this->page = $page ?: 1;
        $this->totalNum = $totalNum;
        $this->initPageNum();
        $this->initStartEndPage();
    }

    public function setPageSize($size)
    {
        if ($size > 1) {
            $this->pageSize = $size;
            $this->initPageNum();
            $this->initStartEndPage();
        }
    }

    public function setButtonRange($range)
    {
        if ($range > 1) {
            $this->buttonRange = $range;
            $this->initStartEndPage();
        }
    }

    public function setPage($page)
    {
        if ($page > 0) {
            $this->page = $page;
            $this->initStartEndPage();
        }
    }

    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @param $key
     * @param mixed $val int|string|array
     */
    public function setParam($key, $val)
    {
        if (is_array($val)) {
            foreach ($val as $v) {
                $this->params["{$key}[]"] = $v;
            }
        } else {
            $this->params[$key] = $val;
        }
    }

    private function initStartEndPage()
    {
        if ($this->pageNum <= $this->buttonRange) {
            $this->startPage = 1;
            $this->endPage = $this->pageNum;
        } else {
            $halfRange = ceil($this->buttonRange / 2);
            if ($this->page <= $halfRange) {
                $this->startPage = 1;
                $this->endPage = $this->buttonRange;
            } else if ($this->page > ($this->pageNum - $halfRange)) {
                $this->startPage = $this->pageNum - $this->buttonRange + 1;
                $this->endPage = $this->pageNum;
            } else {
                $this->startPage = $this->page - $halfRange + 1;
                $this->endPage = $this->page + $halfRange;
            }
        }
    }

    private function initPageNum()
    {
        $this->pageNum = ceil($this->totalNum / $this->pageSize);
        if ($this->page > $this->pageNum) {
            $this->page = $this->pageNum;
        }
    }

    public function startPage()
    {
        return $this->startPage;
    }

    public function endPage()
    {
        return $this->endPage;
    }

    public function page()
    {
        return $this->page;
    }

    public function pageNum()
    {
        return $this->pageNum;
    }

    public function pageSize()
    {
        return $this->pageSize;
    }

    public function totalNum()
    {
        return $this->totalNum;
    }

    public function render($tpl)
    {
        require APP_PATH . '/app/views/' . $tpl . '.phtml';
    }

    public function setHasFirstLast($ok)
    {
        $this->hasFirstLast = $ok;
    }

    public function hasFirstLast()
    {
        return $this->hasFirstLast;
    }

    public function setUrlPath($urlPath)
    {
        $this->urlPath = $urlPath;
    }

    public function url(array $query = [], $urlPath = '')
    {
        $url = $urlPath ?: $this->urlPath;
        if (empty($query['p'])) {
            $query['p'] = $this->page;
        }
        return $url . '?' . http_build_query(array_merge($this->params, $query));
    }

    public function generateTitleUrl(array $query = [], $urlPath = '')
    {
        $url = $urlPath ?: $this->urlPath;
        if (empty($query['p'])) {
            $query['p'] = $this->page;
        }
        $params = array_merge($this->params, $query);
        if (isset($params['sort_k']) && $params['sort_k']) {
            $params['sort_v'] = (isset($params['sort_v']) && $params['sort_v'] == 'asc') ? 'desc' : 'asc';
        }

        return $url . '?' . http_build_query($params);
    }

    public function isCurrent($page)
    {
        return $this->page == $page;
    }
}
