<?php
/**
 * a workflow for ZhiHuDaily.
 * @version 0.5.1
 * @author Bill Cheng(so89898@gmail.com)
 */

define('DEFAULT_CACHE_EXPIRED_TIME', 120);
require 'vendor/workflows.php';

/**
 * Class ZhiHuWorkFlow
 */
class ZhiHuWorkFlow
{

    /**
     * Present the Zhihu Daily.
     */
    public function show()
    {
        $webcode = json_decode($this->getNews(), 1);
        $workflow = new Workflows();
        $order = array("\r\n", "\n", "\r");
        for($i=0;$i<count($webcode['news']);$i++){
            $title = str_replace($order, " · ", $webcode['news'][$i]['title']);
            $workflow->result($i, $webcode['news'][$i]['share_url'], $title, null, $this->getNewsIconFilePath($webcode['news'][$i]['thumbnail'], null));
        }

        echo $workflow->toxml();
    }

    /**
     * Get news data from cache or remote.
     */
    public function getNews() {
        $cacheFile = 'cache_news.dat';
        $newsData = false;
        if (is_file($cacheFile)) {
            $cacheObject = unserialize(file_get_contents($cacheFile));
            if ($cacheObject['expiredTime'] >= time()) {
                $newsData = $cacheObject['newsData'];
            }
        }

        if (!$newsData) {
            $newsData = file_get_contents('http://news.at.zhihu.com/api/1.2/news/latest');
            $expiredTime = time() + (defined('CACHE_EXPIRED_TIME') ? CACHE_EXPIRED_TIME : DEFAULT_CACHE_EXPIRED_TIME);
            file_put_contents($cacheFile, serialize((compact('expiredTime', 'newsData'))));
        }
        return $newsData;
    }


    /**
     * Get news icon file path.
     *
     * @param string $iconUrl
     * @param string $iconDir
     *
     * @return string
     */
    private function getNewsIconFilePath($iconUrl = '', $iconDir = null)
    {
        if (!$iconUrl) {
            $iconPath = 'icon.png';
        } else {
            $iconName = basename(($iconUrl));
            $iconDir = $iconDir ?: 'icons/news/';
            $iconPath = $iconDir . DIRECTORY_SEPARATOR . $iconName;

            if (!file_exists($iconPath)) {
                $this->downloadIcon($iconUrl, $iconPath);
            }
            $iconPath = file_exists($iconPath) ? $iconPath : 'icon.png';
        }

        return $iconPath;
    }

    /**
     * Download news icon.
     *
     * @param $url      string
     * @param $savePath string
     */
    private function downloadIcon($url, $savePath)
    {
        $dir = dirname($savePath);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $context = stream_context_create(array(
                'http' => array(
                    'method' => "GET",
                    'timeout' => 30
                )
            )
        );
        file_put_contents($savePath, file_get_contents($url, 0, $context));
    }
}
    