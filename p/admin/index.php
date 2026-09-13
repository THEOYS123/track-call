<?php

$file = "../data/secret_codes.json";

$data = json_decode(file_get_contents($file),true);

?>

<h2>SECRET CODE ADMIN PANEL</h2>

<form action="save_code.php" method="post">

<input type="text" name="code" placeholder="Kode Rahasia">

<input type="number" name="uses" placeholder="Jumlah Penggunaan">

<button type="submit">Buat Kode</button>

</form>

<hr>

<table border="1">

<tr>
<th>Kode</th>
<th>Sisa</th>
<th>Status</th>
</tr>

<?php foreach($data as $d){ ?>

<tr>

<td><?php echo $d["code"]; ?></td>

<td><?php echo $d["uses_left"]; ?></td>

<td><?php echo $d["status"]; ?></td>

</tr>

<?php } ?>

</table>
