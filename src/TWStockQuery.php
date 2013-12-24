<?php
require_once('workflows.php');

function StockQuery($request)
{
	$requestParts = explode(' ', $request);

	if(count($requestParts) >= 2 ){
		$querystring = preg_split('/\s+/', trim(stripslashes($request)));
		$cmd = $querystring[0];
		$num = $querystring[1];
		$length  = strlen($num);
		if($cmd == "add" && $length == 4){
			add($num);
		} else if($cmd == "delete" && $length == 4){
			deleteKey($num);
		} else {
			message("支援的參數為 add delete list ");
		}
	} else{
		if($request == "list"){
			listAll();
		} else {
			base($request);
		}

	}
}

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
			}
		}

    } else {
    	$num = $request;
    }
	$result = '<?xml version="1.0" encoding="utf-8"?><items>';
    $result .=queryStock($num);
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
	if(in_array($no, $json)){
		message($no.'  已經重複了');
	} else {
		message($no.'  增加成功');
		$json []=$no;
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
	    	message($no." 刪除成功");
	    }
	}
	$fp = fopen($fileName, 'w');
	fwrite($fp, json_encode(array_values($json)));
	fclose($fp);
}

function message($title, $detail = "") {
   $w = new Workflows();
   $w -> result('0','null',$title,$detail,'icon.png');
   echo $w->toxml();
   echo "\n";
}
// StockQuery("1234"); //正確的
// StockQuery("12345"); //錯誤的
// StockQuery("鴻海");// 正確的
// StockQuery("馬英九");//錯誤的
// StockQuery("delete 0060");//delete stock
// StockQuery("list");//查詢list
// StockQuery("add 0060");//add stock
// StockQuery("list");//查詢list
?>
