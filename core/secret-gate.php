<?php

function validate_secret_code($code){

$file = __DIR__ . '/../data/secret_codes.json';

if(!file_exists($file)){
return false;
}

$data = json_decode(file_get_contents($file), true);

foreach($data as $i => $c){

if($c["code"] === $code){

if($c["status"] !== "active"){
return false;
}

if($c["uses_left"] <= 0){
return false;
}

$data[$i]["uses_left"] -= 1;

if($data[$i]["uses_left"] <= 0){
$data[$i]["status"] = "inactive";
}

file_put_contents($file,json_encode($data,JSON_PRETTY_PRINT));

return true;

}

}

return false;

}

function censor_result($data){

if(!is_array($data)){
return $data;
}

foreach($data as $k=>$v){
$data[$k] = "******";
}

return $data;

}

?>
