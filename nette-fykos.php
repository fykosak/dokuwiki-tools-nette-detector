<?php

$links = array();
$p = '/';

ini_set('max_execution_time', 300); //300 seconds = 5 minutes

echo '<table>';
getResponse($p, $links);

function getResponse($page, &$links) {
    if ($page == "" || $page == "psutils/") {
        return;
    }
    if (preg_match('#http://fykos#', $page) || preg_match('#https://fykos#', $page)) {
        $r = new HttpRequest($page, HttpRequest::METH_GET);
    } elseif (preg_match('#http#', $page) || preg_match('/\_media/', $page) || preg_match('/mailto:/', $page) || preg_match('/\./', $page)) {
        return;
    } else {
        $r = new HttpRequest("http://fykos.cz" . $page, HttpRequest::METH_GET);
    }
    try {
        $r->send();
        if (in_array($r->getResponseCode(), [200, 300, 301, 302])) {
            $html = $r->getResponseBody();
            $dom = new DOMDocument;
            @$dom->loadHTML($html);
            $metaTags = $dom->getElementsByTagName('meta');
            $isWiki = false;
            foreach ($metaTags as $meta){
                /**
                 * @var $meta DOMElement
                 */
                if ($meta->getAttribute('name') == 'generator' && $meta->getAttribute('content') == 'DokuWiki') {
                    $isWiki = true;
                }
            }
            $as = $dom->getElementsByTagName('a');
            $m = array();
            foreach ($as as $a) {
                /**
                 * @var $a DOMElement
                 */
                $m[] = $a->getAttribute('href');
            }
            unset($dom);
            if ($isWiki) {
                echo '<tr style="background-color: #77FF77"><td>' . $r->getResponseCode() . '</td><td>' . "OK" . '</td><td>' . $page . '</tr>';
            } else {
                echo '<tr style="background-color: #FFFF77"><td>' . $r->getResponseCode() . '</td><td>' . "No Dokuwiki:" . '</td><td>' . $page . '</tr>';
            }
            unset($html);

            foreach ($m as $link) {
                $link = str_replace(['http://fykos.cz', 'http://fykos.org'], "", $link);
                $link = preg_replace('/#.*/', '', $link);
                $link = preg_replace('/\?.*/', '', $link);
                if (!in_array($link, $links)) {
                    $links[] = $link;
                    sleep(0.001);
                    getResponse($link, $links);
                    ob_flush();
                    flush();
                }
            }
        } else {
            echo '<tr style="background-color: #ff2222"><td>' . $r->getResponseCode() . '</td><td></td><td>' . $page . '</tr>';
        }
    } catch (HttpException $ex) {
        echo $ex;
    }
    ob_flush();
    flush();
}
