<?php

function StockQuery($request)
{
	$requestParts = explode(' ', $request);
	if(count($requestParts) >= 2 ){
		if($requestParts[0] == "add"){
			add($requestParts[1]);
		} else if($requestParts[0] == "delete"){
			deleteKey($requestParts[1]);
		} else {
			echo "執行有誤";
		}
	} else{
		if($request == "list"){
			listAll();
		} else {
			base($request);
		}

	}

	//echo $result;

}





$dirname = dirname(__FILE__);


function base($request){
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
    $result .=queryStock($request);
	$result .= '</items>';
	echo $result;
}

function queryStock($no){
	$url = 'http://mis.tse.com.tw/data/'.$no.'.csv?r='.$no;
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
		$result = "";
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
			$result .= '<subtitle>最後更新時間：'.$value -> time.'</subtitle>';
			$result .= '<icon>icon.png</icon>';
			$result .= '</item>';
		} else {
			$result .= '<item uid="mtranslate">';
			$result .= '<title>'.$no.'  沒有資料</title>';
			$result .= '<icon>icon.png</icon>';
			$result .= '</item>';
		}
	return $result;
}
function add($no){
	$fileName = 'results.json';
	$handle = fopen($fileName, "r");
	$contents = fread($handle, filesize($fileName));
	fclose($handle);
	$json = json_decode($contents, true);
	echo $contents;
	if(in_array($no, $json)){
		echo $no." "."已經存在";
		echo "\n";
	} else {
		$json[] = $no;
	}
	$fp = fopen($fileName, 'w');
	fwrite($fp, json_encode($json));
	fclose($fp);
}

function listAll(){
	$fileName = 'results.json';
	$handle = fopen($fileName, "r");
	$contents = fread($handle, filesize($fileName));
	$json = json_decode($contents, true);
	$result = '<?xml version="1.0" encoding="utf-8"?><items>';
	foreach ($json as $i => $value) {
    	$result .=queryStock($json[$i]);

	}
	$result .= '</items>';
	echo $result;
}


function deleteKey($no){
	$fileName = 'results.json';
	$handle = fopen($fileName, "r");
	$contents = fread($handle, filesize($fileName));
	$json = json_decode($contents, true);
	foreach ($json as $i => $value) {
	    if($json[$i] == $no){
	    	unset($json[$i]);
	    }
	}
	$fp = fopen($fileName, 'w');
	fwrite($fp, json_encode(array_values($json)));
	fclose($fp);
}


// StockQuery("1234"); //正確的
// StockQuery("12345"); //錯誤的
// StockQuery("鴻海");// 正確的
// StockQuery("馬英九");//錯誤的
// StockQuery("list");//查詢list
// StockQuery("delete 2526");//delete stock
// StockQuery("list");//查詢list
// StockQuery("add 2526");//add stock
// StockQuery("list");//查詢list
?>
