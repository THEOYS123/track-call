<?php

$file = "../data/secret_codes.json";

$data = json_decode(file_get_contents($file),true);

$code = $_POST["code"];
$uses = (int)$_POST["uses"];

$data[] = [

"code"=>$code,
"uses_left"=>$uses,
"status"=>"active"

];

file_put_contents($file,json_encode($data,JSON_PRETTY_PRINT));

header("Location:index.php");

?>
