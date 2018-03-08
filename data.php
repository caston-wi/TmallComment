<?php
/**
 * 抓取天猫评论（评论)
 * 包含:①显示出来的数据;②被折叠起来的数据
 * @author: w
 * @date: 2017/1/23
 * @time: 14:40
 * @timeFirst: 2016/9/30
 */

header("Content-Type: text/html;charset=utf-8");
date_default_timezone_set("PRC");
set_time_limit(0);

/**
 * 字符串编码转换成utf8
 * @param $str
 * @return string
 */
function gbk2utf8($str){
    $charset = mb_detect_encoding($str,['UTF-8','GBK','GB2312']);
    $charset = strtolower($charset);
    if('cp936' == $charset){
        $charset='GBK';
    }
    if("utf-8" != $charset){
        $str = iconv($charset,"UTF-8//IGNORE",$str);
    }
    return $str;
}

/**
 * CURL请求
 * @param $url
 * @param array $post
 * @param array $header
 * @param string $cookie
 * @param bool $ishttps
 * @param bool $isgzip
 * @return string
 */
function getWeb($url, $post=[], $header=[], $cookie='', $ishttps=false, $isgzip=false)
{
    $action_ip = rand(1,255).'.'.rand(1,255).'.'.rand(1,255).'.'.rand(1,255);
    $header_ip = [
        'CLIENT-IP:'.$action_ip,
        'X-FORWARDED-FOR:'.$action_ip,
    ];
    $header = array_merge($header,$header_ip);
    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_URL, $url);
    if (!empty($post)){
        curl_setopt ($ch, CURLOPT_POST, 1);
        curl_setopt ($ch, CURLOPT_POSTFIELDS, $post);
    }
    curl_setopt ($ch, CURLOPT_HTTPHEADER, $header);
    if (!empty($cookie)){
        curl_setopt ($ch, CURLOPT_COOKIE, $cookie);
    }
    if ($ishttps){
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    if ($isgzip){
        curl_setopt ($ch, CURLOPT_ENCODING, 'gzip');
    }
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/53.0.2785.116 Safari/537.36');
    curl_setopt ($ch, CURLOPT_HEADER, 0);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 30);
    $web_code = curl_exec($ch);
    curl_close($ch);
    $web_code = gbk2utf8($web_code);
    return $web_code;
}

/**
 * 正常显示评论处理
 * @param $itemId
 * @param $spuId
 * @param $sellerId
 * @param int $page
 * @return array
 */
function getTmall($itemId, $spuId, $sellerId, $page=1)
{
    $url = 'https://rate.tmall.com/list_detail_rate.htm?itemId='.$itemId.'&spuId='.$spuId.'&sellerId='.$sellerId.'&order=1&currentPage='.$page.'&append=0&content=0&tagId=&posi=&picture=&ua=219UW5TcyMNYQwiAiwQRHhBfEF8QXtHcklnMWc%3D%7CUm5OcktxSXZLdkN4R3JMdyE%3D%7CU2xMHDJ7G2AHYg8hAS8XKQcnCVU0Uj5ZJ11zJXM%3D%7CVGhXd1llXGZeYVxhVG9QZVtjVGlLc01ySH1Fe0B8SHRAfUB7RGo8%7CVWldfS0RMQkxCysXLw8hQyEFLl0PYT9SPVMxWCFzXQtd%7CVmhIGCUFOQ0yDy8TLxsmBjgNOQYmGiQfJAQ%2BBTAQLBIpEjIINwJUAg%3D%3D%7CV25OHjAePgQ6BCQYJRAsDDcNNA40YjQ%3D%7CWGFBET8RMQgyCioeIRs7ATgAOQdRBw%3D%3D%7CWWBAED4QMA47BSUZJBsjAzkBPAU%2BaD4%3D%7CWmJCEjwSMmJcZlNzT3JNeC4OMxM9EzMJMQ4zDVsN%7CW2JfYkJ%2FX2BAfEV5WWdfZUV8XGBdfUlpXHxAey0%3D&isg=ArOzZjzd4Lc28qxBxflSf2UDQrfNeUeqMQwkGWVQD1IJZNMG7bjX-hH2YCNw&needFold=0&_ksTS=&callback=&isg=&isg2=&isg=';
    $cookie = '_tb_token_=9iC4yHMamQzk; cookie2=73bab71598835af8df2e1b9ffc7a22b6; t=9d6dee754d343a7b624d320bed73682e; isg=AggI54S-y8X_KCdg4tCpDroa2XY3y2y7fmXv-MK5RgN2nagHasE8S56da8IX; JSESSIONID=103131A9191735D596BE67A1491E7BF6';
    $webcode = getWeb($url,[],[],$cookie,true);
    $webcode = str_replace('"rateDetail":','',$webcode);
    $webcode = json_decode($webcode,true);
    if (empty($webcode['rateList'])){
        return getTmall($itemId, $spuId, $sellerId, $page);
    }
    $comment = $webcode['rateList'];
    $comment_arr = [];
    foreach ($comment as $k=>$v){
        $time = strtotime($v['rateDate']);
        $time = substr($time,0, 10);
        $time = date('Ym', $time);
        $comment_arr[] = $time;
    }
    return ['list'=>$comment_arr, 'page'=>$webcode['paginator']];
}

/**
 * 折叠起来的评论处理
 * @param $itemId
 * @param $spuId
 * @param $sellerId
 * @param int $page
 * @return array
 */
function getTmall_other($itemId, $spuId, $sellerId, $page=1)
{
    $url = 'https://rate.tmall.com/list_detail_rate.htm?itemId='.$itemId.'&spuId='.$spuId.'&sellerId='.$sellerId.'&order=1&currentPage='.$page.'&append=0&content=0&tagId=&posi=&picture=&ua=219UW5TcyMNYQwiAiwQRHhBfEF8QXtHcklnMWc%3D%7CUm5OcktxSXZLdkN4R3JMdyE%3D%7CU2xMHDJ7G2AHYg8hAS8XKQcnCVU0Uj5ZJ11zJXM%3D%7CVGhXd1llXGZeYVxhVG9QZVtjVGlLc01ySH1Fe0B8SHRAfUB7RGo8%7CVWldfS0RMQkxCysXLw8hQyEFLl0PYT9SPVMxWCFzXQtd%7CVmhIGCUFOQ0yDy8TLxsmBjgNOQYmGiQfJAQ%2BBTAQLBIpEjIINwJUAg%3D%3D%7CV25OHjAePgQ6BCQYJRAsDDcNNA40YjQ%3D%7CWGFBET8RMQgyCioeIRs7ATgAOQdRBw%3D%3D%7CWWBAED4QMA47BSUZJBsjAzkBPAU%2BaD4%3D%7CWmJCEjwSMmJcZlNzT3JNeC4OMxM9EzMJMQ4zDVsN%7CW2JfYkJ%2FX2BAfEV5WWdfZUV8XGBdfUlpXHxAey0%3D&isg=ArOzZjzd4Lc28qxBxflSf2UDQrfNeUeqMQwkGWVQD1IJZNMG7bjX-hH2YCNw&needFold=1&_ksTS=&callback=&isg=&isg2=&isg=';
    $cookie = '_tb_token_=9iC4yHMamQzk; cookie2=73bab71598835af8df2e1b9ffc7a22b6; t=9d6dee754d343a7b624d320bed73682e; isg=AggI54S-y8X_KCdg4tCpDroa2XY3y2y7fmXv-MK5RgN2nagHasE8S56da8IX; JSESSIONID=103131A9191735D596BE67A1491E7BF6';
    $webcode = getWeb($url,[],[],$cookie,true);
    $webcode = str_replace('"rateDetail":','',$webcode);
    $webcode = json_decode($webcode,true);
//    exit;
    if (empty($webcode['rateList'])){
        return getTmall($itemId, $spuId, $sellerId, $page);
    }
    $comment = $webcode['rateList'];
    $comment_arr = [];
    foreach ($comment as $k=>$v){
        $time = strtotime($v['rateDate']);
        $time = substr($time,0, 10);
        $time = date('Ym', $time);
        $comment_arr[] = $time;
    }
    return ['list'=>$comment_arr, 'page'=>$webcode['paginator']];
};


//程序开始
//exit;
//开始时请将下面的url替换为需要查找页面的list_detail_rate.htm文件对应的网址(在network中寻找list_detail_rate.htm，点击打开复制url)
$url = 'https://rate.tmall.com/list_detail_rate.htm?itemId=529736019723&spuId=528747862&sellerId=1954193971&order=1&currentPage=9&append=0&content=0&tagId=&posi=&picture=&ua=046UW5TcyMNYQwiAiwQRHhBfEF8QXtHcklnMWc%3D%7CUm5Ockt%2BRnpCfEN8QHpHciQ%3D%7CU2xMHDJ7G2AHYg8hAS8XLwEhD1MyVDhfIVt1I3U%3D%7CVGhXd1llXGlRbVVrVGtXbVBlUm9NeEF9SXVOdUpyR3JIdUlxSGYw%7CVWldfS0RMQ0xCjISLhY2GCEYJA46ACUSbQR%2FUz5bJQtdCw%3D%3D%7CVmhIGCUFOhomHyUaOgY%2BBzgMLBAkGyYGOgcyDy8TJxglBTkEPAFXAQ%3D%3D%7CV29PHzEfP29bblt7R35Dd0trX2Vbe0N4RWdcaVNrS3VPb1FlMxMuDiAOLhEtGCRyJA%3D%3D%7CWGFcYUF8XGNDf0Z6WmRcZkZ%2FX2NXAQ%3D%3D&isg=AnZ2nbMTMkSf6cZYuNOO1m9Gx6x9y7rRd39ByuBfYtn0Ixa9SCcK4dxRTUi1&needFold=0&_ksTS=1485153223978_2824&callback=jsonp2825&isg2=Au%2FvpUJyZmNnFcz-XlOjC%2FZH%2Fwn7sUNU';
//$url = base64_decode($url);
$url = explode('?',$url);
$url = explode('&',$url[1]);
$get = [];
foreach ($url as $v){
    $v = explode('=',$v);
    $get[$v[0]] = $v[1];
}

$itemId = $get['itemId'];
$spuId = $get['spuId'];
$sellerId = $get['sellerId'];
$page = getTmall($itemId, $spuId, $sellerId, 1);
$page_other = getTmall_other($itemId, $spuId, $sellerId, 1);
$time_arr = [];
for ($i=1; $i<=$page['page']['lastPage']; $i++){
    $comment = getTmall($itemId, $spuId, $sellerId, $i);
    foreach ($comment['list'] as $time){
        $time_arr[] = $time;
    }
}
$time_arr_other = [];
for ($i=1; $i<=$page_other['page']['lastPage']; $i++){
    $comment = getTmall_other($itemId, $spuId, $sellerId, $i);
    foreach ($comment['list'] as $time){
        $time_arr_other[] = $time;
    }
}
$time_arr = array_merge($time_arr,$time_arr_other);
//统计

$count = array_count_values($time_arr);
echo count($time_arr);
echo "\r\n";
echo "<pre/>";
print_r($count);

