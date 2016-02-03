<?php

$obj = new prtrDetail();
$obj->go();

class prtrDetail {

    public $cachePath;
    public $cPage = 0;
    public $totalPages = 0;
    public $fh = false;
    public $reports = array(
        //空氣污染
        'AIR' => 'http://prtr.epa.gov.tw/FacilityInfo/_DetailAirChart?registrationno=',
        //水污染
        'WAT' => 'http://prtr.epa.gov.tw/FacilityInfo/_DetailChart?poltype=WAT&registrationno=',
        //廢棄物
        'WAS' => 'http://prtr.epa.gov.tw/FacilityInfo/_DetailChart?poltype=WAS&registrationno=',
        //毒性化學物質
        'TOX' => 'http://prtr.epa.gov.tw/FacilityInfo/_DetailChart?poltype=TOX&registrationno=',
        //有害空氣污染
        'METAL' => 'http://prtr.epa.gov.tw/FacilityInfo/_DetailChart?poltype=METAL&registrationno=',
        //溫室氣體
        'GREENHOUSE' => 'http://prtr.epa.gov.tw/FacilityInfo/_DetailChart?poltype=GREENHOUSE&registrationno=',
        //裁處資訊
        'PENALTY' => 'http://prtr.epa.gov.tw/FacilityInfo/_DetailChart?poltype=PENALTY&registrationno=',
    );

    function __construct() {
        $this->cachePath = dirname(__DIR__) . '/tmp/detail';
    }

    public function go() {
        $fh = fopen(dirname(__DIR__) . '/list.csv', 'r');
        /*
         * Array
          (
          [0] => name
          [1] => id
          [2] => category
          [3] => type
          [4] => address
          )
         */
        fgetcsv($fh, 2048);
        while ($line = fgetcsv($fh, 2048)) {
            error_log('processing ' . $line[1]);
            $url = 'http://prtr.epa.gov.tw/FacilityInfo/DetailIndex?registrationno=' . $line[1];
            $cachePath = $this->cachePath . '/' . substr($line[1], 0, 1);
            if (!file_exists($cachePath)) {
                mkdir($cachePath, 0777, true);
            }
            $cacheFile = $cachePath . '/' . $line[1];
            if (!file_exists($cacheFile)) {
                error_log('getting ' . $line[1]);
                file_put_contents($cacheFile, file_get_contents($url));
            }
            $page = file_get_contents($cacheFile);
            $data = array(
                'info' => array(),
                'reports' => array(),
            );
            $data['info'] = $this->parseInfo($page);
            $data['info']['name'] = $line[0];
            foreach ($this->reports AS $k => $url) {
                $data['reports'][$k] = json_decode(file_get_contents($url . $line[1]));
            }

            $targetPath = dirname(__DIR__) . '/data/' . substr($line[1], 0, 2);
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0777, true);
            }
            file_put_contents($targetPath . '/' . $line[1] . '.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public function parseInfo($c) {
        $pos = strpos($c, '<div class="detailInfo">');
        $posEnd = strpos($c, '<div id="divNavibarWrap"', $pos);
        $part = trim(strip_tags(substr($c, $pos, $posEnd - $pos)));
        $part = str_replace(array('&nbsp;', '　'), '', $part);
        $lines = explode("\n", $part);
        $result = array();
        foreach ($lines AS $line) {
            $cols = explode('：', trim($line));
            if (count($cols) === 2) {
                $result[$cols[0]] = $cols[1];
            }
        }
        $pos = strpos($c, '<input type="hidden" id="hidLat" value="');
        $posEnd = strpos($c, '" ', $pos + 40);
        $result['latitude'] = substr(substr($c, $pos, $posEnd - $pos), 40);
        $pos = strpos($c, '<input type="hidden" id="hidLon" value="');
        $posEnd = strpos($c, '" ', $pos + 40);
        $result['longitude'] = substr(substr($c, $pos, $posEnd - $pos), 40);
        return $result;
    }

}
