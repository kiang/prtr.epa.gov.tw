<?php

$obj = new prtrList();
$obj->go();

class prtrList {

    public $cachePath;
    public $cPage = 0;
    public $totalPages = 0;
    public $fh = false;

    function __construct() {
        $this->cachePath = dirname(__DIR__) . '/tmp/list';
        if (!file_exists($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
        $this->fh = fopen(dirname(__DIR__) . '/list.csv', 'w');
        fputcsv($this->fh, array('name', 'id', 'category', 'type', 'address'));
    }

    public function go() {
        $this->totalPages = $this->extractPageNum($this->getPageFile(1));
        for ($i = 1; $i <= $this->totalPages; $i++) {
            $lines = $this->extractLines($this->getPageFile($i));
        }
    }

    public function getPageFile($pageNum) {
        $url = 'http://prtr.epa.gov.tw/FacilityInfo/_Data?keyword=%E8%99%9F&page=' . $pageNum;
        $cacheFile = $this->cachePath . '/' . $pageNum;
        if (!file_exists($cacheFile)) {
            error_log('fetching page #' . $pageNum);
            file_put_contents($cacheFile, file_get_contents($url));
        }
        return file_get_contents($cacheFile);
    }

    public function extractPageNum($c) {
        $parts = explode('"hidTotalItemCount" value="', $c);
        $pos = strpos($parts[1], '"');
        $itemCount = intval(substr($parts[1], 0, $pos));
        $pageCount = $itemCount / 10;
        if($itemCount % 10 !== 0) {
            ++$pageCount;
        }
        return $pageCount;
    }

    public function extractLines($c) {
        $c = substr($c, strpos($c, '<tr class="data_tr">'));
        $lines = explode('</tr>', $c);
        foreach ($lines AS $line) {
            $line = str_replace("\r\n", ' ', $line);
            $cols = preg_split('/[ \\/]+/', trim(strip_tags($line)));
            foreach($cols AS $k => $v) {
              $cols[$k] = trim($v);
            }
            if (count($cols) === 7) {
                fputcsv($this->fh, array(
                  $cols[0],
                  $cols[3],
                  $cols[4],
                  $cols[5],
                  $cols[6],
                ));
            }
        }
    }

}
