<style>
table.maintable {
    border:solid 5px #393837;
    border-collapse:collapse;
	font-weight: bold;
    font-size:26px;
    font-family:Arial, Verdana, Tahoma, Helvetica, sans-serif;
    text-decoration:none;
    color:#0018ff; 
	padding: 10px;
}

table.maintable th {
  background-color:#4B4B4B;
}

table.maintable tr {
vertical-align: top;
}

table.datatable {
    font-size: 13px;
	padding: 5px;
}

.compute_tile {
    color: blue;
	font-size: 12px;
    background:rgba(0,0,0,0);
    border:none;
	disabled:true;
	readonly:true;
	padding: 5px;
	text-align:center;
} 

.compute {
    color: blue;
	font-family: monospace;
	font-size: 12px;
    background:rgba(0,0,0,0);
    border:none;
	padding: 3px;
	disabled:true;
	readonly:true;
	text-align:right;
} 

.hash_text {
    font-family: monospace;
	font-size: 12px;
}
.hash_label {
	text-align:right;
	font-size: 12px;
}

.progress{
        position:relative;
        width:900px;
		height:20px;
        background-color:white ;
        position:relative;
        padding:1px;
    }
    .bar_red{
        background-color:#ffb7bd;
        width:0%;
        height:20px;
    }
.bar_green{
        background-color:#caf9c0;
		position:absolute;
		top:10px;
        width:0%;
        height:10px;
    }
    .percent{
        position:absolute;
        display:inline-block;
		font-family: monospace;
	font-size: 12px;
        top:2px;
        left:2px;
    }



</style>

<html>
<head>
<title>Hash Algorithm Demo</title>

   <?php 
    if(isset($_POST['hash'])){
		$hash_text = $_POST["hash_text"];
	
		$hash_len = strlen($hash_text);

		$hash_array=array();
		$time_array=array();
		$coll_array=array();
		
		foreach (hash_algos() as $ha) { 
			$start = microtime(true);
			$hash_array[$ha] = hash($ha, $hash_text); 
			$end = microtime(true);
			$time_array[$ha] = round(($end - $start)*1000,5) . " ms";
			$coll_array[$ha] = pow(2,-strlen($hash_array[$ha])*2);
			
		}
	}
    ?>

</script>
</head>

<body onLoad=clear()>
<table class="maintable" style="background-color:#fdf5e6; border: 5px solid black">
	<tr><td style="color:#393837; text-align:center; padding:10px; vertical-align:bottom"><img src="carechain_logo.png" align="right" height="60" width="72"><br>Hash Algorithm Demo</td></tr>
	<tr><td style="padding:10px">
		<table class="datatable"  style="background-color:#D5D5D5">
			<form name="demo_form" method="post">
			<tr><td style="text-align:right;font-weight: bold;paddig:5px">Hash String</td> 
				<td><textarea class="hash_text" name="hash_text"  size="45" cols="128 "rows="5" style="height:120px"><?php if(isset($hash_text)){ echo $hash_text;} else {echo ''; }?></textarea></td></tr>
			<tr><td style="text-align:right;font-weight: bold">Hash String Length</td> <td><input type="number" name="size" size="20" value="<?php if(isset($hash_text)){ echo $hash_len;}?>"> &nbsp; 
				<input type="submit" name="hash" value="hash"></td> </tr>
			</form>
		</table> 
	</td></tr>
	<tr><td style="padding:0px 10px 10px 10px">
		<table class="datatable" style="background-color:#D5D5D5;padding:0">
		<tr style="font-weight:bold;font-size:11px;background:#abaeb5">
		<td style="text-align:center;padding:3px">Hash<br>Algorithm</td><td style="padding:10px 10px 10px 10px">&nbsp Hash Values in Hexa-Decimal</td><td style="padding:3px">Bits <br>2<sup>2</sup> x Len</td> 
		<td style="padding:3px">Elapsed Time <br>in milli seconds</td><td style="text-align:center ">Collision<br>Probability 2<sup>n/2</sup></tr>
<?php
	$max_time=max($time_array); //-min($time_array);
	$coll_diff=max($coll_array); //-min($coll_array);
	
	$percent = 0;
	foreach (hash_algos() as $ha) {
		if ($max_time>0) {
			$time_percent = round(($time_array[$ha]/$max_time)*100);
		}
		if ($time_percent > 100) {
			$time_percent = 100;
		}
		$bits = strlen($hash_array[$ha])*4;
		
		echo'<tr><td class="hash_label">'.$ha.'</td><td><div class="progress"><div class="bar_red" style="width:'.$time_percent.'%"></div ><div class="percent">'
		.$hash_array[$ha].'</div ></div></td> '.
			'<td class="compute">'.$bits.'</td> '.
			'<td class="compute" style="color:#561319">'.$time_array[$ha].'</td><td class="compute" style="padding-left:6px;color:green">'.$coll_array[$ha].'<td></tr> ';
			}
?>
<!--
				<tr><td class="hash_label">SHA384</td> <td><textarea class="hash_text" size="90" cols="70" rows="2" style="height:40px"><?php if(isset($sha384_value)){ echo $sha384_value;}?></textarea></td>
				<td><input class="compute" size=6 value="<?php if(isset($sha384_value)){ echo strlen($sha384_value);}?>" readonly></td>
				<td><input class="compute" size=12 value="<?php if(isset($sha384_value)){ echo $sha384_time;}?>" readonly></td></tr> -->
		</table>
	</td></tr>
</table>

</body>
</html>