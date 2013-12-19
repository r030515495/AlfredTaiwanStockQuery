<?php

function StockQuery($request)
{

    if(!is_numeric($request)){
    	$numQueryUrl = 'http://goristock.appspot.com/API/searchstock?q='.urlencode($request);
    	$curlSet = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $numQueryUrl,
			CURLOPT_FRESH_CONNECT => true
		);
		$ch  = curl_init();
		curl_setopt_array($ch, $curlSet);
		$out = curl_exec($ch);
		$err = curl_error($ch);
		curl_close($ch);

		$json = json_decode(htmlspecialchars_decode($out));
		$url = "";
		if($json -> n > 0){
			if($json -> result){
				foreach (($json -> result) as $key => $value) {
					$num= $key;
				}
				$url = 'http://mis.tse.com.tw/data/'.$num.'.csv?r='.$num;
			}
		}

    } else {
		$url = 'http://mis.tse.com.tw/data/'.$request.'.csv?r='.$request;
    }
	$result = '<?xml version="1.0" encoding="utf-8"?><items>';
    if(!$url){
    	$result .= '<item uid="mtranslate">';
		$result .= '<title>查無此名稱：'.$request.'</title>';
		$result .= '<icon>icon.png</icon>';
		$result .= '</item>';

    } else {
    	$defaults = array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_URL => $url,
			CURLOPT_FRESH_CONNECT => true
		);

		$ch  = curl_init();
		curl_setopt_array($ch, $defaults);
		$out = curl_exec($ch);
		$err = curl_error($ch);
		curl_close($ch);
		if(!strrpos($out,"404 Not Found")){
			$value =new stdClass();;
			$out = str_replace("\"","",$out);
			$arr = explode(",",$out);
			$value -> name = iconv("big5","UTF-8",$arr[36]);
		    $value -> no = $arr[0];
		    $value -> rang = doubleval($arr[1]);
		    if($value -> rang > 0){
		    	$value -> rang = "+".$arr[1] ;
			}
		    $value -> time = $arr[2];
		    $value -> c = $arr[8];
			$result .= '<item uid="mtranslate">';
			$result .= '<title>'.$value -> no.' '.$value -> name.' '.$value -> c.' '.$value -> rang.'</title>';
			$result .= '<subtitle>'.$value -> time.'</subtitle>';
			$result .= '<icon>icon.png</icon>';
			$result .= '</item>';
		} else {
			$result .= '<item uid="mtranslate">';
			$result .= '<title>沒有資料</title>';
			$result .= '<icon>icon.png</icon>';
			$result .= '</item>';
		}

    }


	$result .= '</items>';
	echo $result;
}
 // StockQuery("1234"); //正確的
 // StockQuery("12345"); //錯誤的
 // StockQuery("鴻海");// 正確的
 // StockQuery("馬英九");//錯誤的

?>
