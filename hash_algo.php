<style>
table.maintable {
    border:solid 5px #393837;
    border-collapse:collapse;
	font-weight: bold;
    font-size:20px;
    font-family:Arial, Verdana, Tahoma, Helvetica, sans-serif;
    text-decoration:none;
    color:#0018ff; 
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
        background-color:#ffe2e2;
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
<title>Hash Algorithm</title>

   <?php 
    if(isset($_POST['hash'])){
		$hash_text = $_POST["hash_text"];
	
		$hash_len = strlen($hash_text);
		$algo_array = hash_algos();
		$hash_array=array();
		$time_array=array();
		$coll_array=array();
		
		foreach ($algo_array as $ha) { 
			$start = microtime(true);
			$hash_array[$ha] = hash($ha, $hash_text); 
			$end = microtime(true);
			$time_array[$ha] = round(($end - $start)*1000*1000,5); 
			$coll_array[$ha] = pow(2,-strlen($hash_array[$ha])*2);
			
		}
	}
    ?>
<script>
var randomString = function() {
    var text = "";
	var length = document.getElementById("hash_len").value
    var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZaaaaabbccddeeeeffgghhiiiiijkklkmmmnnnoooooppqrrssstttuuvwxyz0123456789                  ,.;:'+()-_@";
    for(var i = 0; i < length; i++) {
        text += possible.charAt(Math.floor(Math.random() * possible.length));
    }
	document.getElementById("hash_text").value=text;
    return text;
}

</script>
</head>

<body onLoad=clear()>
<table class="maintable" style="background-color:#fdf5e6; border:5px solid black">
	<tr><td style="color:#393837; text-align:center; vertical-align:bottom"><img src="carechain_logo.png" align="right" height="60" style="margin:0px 30px"><br>Hash Algorithm</td></tr>
	<tr><td style="padding:10px">
		<table class="datatable" style="background-color:#D5D5D5;width:100%">
			<form name="demo_form" method="post">
			<tr><td style="text-align:right;font-weight: bold;padding:5px">Hash String<br><br><input type="button" value="Generate Random Text" onClick = randomString()></td> 
				<td><textarea class="hash_text" name="hash_text"  id="hash_text" size="45" cols="150 "rows="8" style="height:80px"><?php if(isset($hash_text)){ echo $hash_text;} else {echo ''; }?></textarea></td></tr>
			<tr><td style="text-align:right;font-weight: bold">Hash String Length</td><td><input type="number" name="hash_len" id="hash_len" size="20" value="<?php if(isset($hash_text)){ echo $hash_len;}?>"> &nbsp; 
				<input type="submit" name="hash" value="hash"></td> </tr>
			</form>
		</table> 
	</td></tr>
	<tr><td style="padding:0px 10px 10px 10px">
		<table class="datatable" style="background-color:#D5D5D5;padding:0">
		<tr style="font-weight:bold;font-size:11px;background:#abaeb5">
		<td style="text-align:center;padding:3px">Hash<br>Algorithm</td><td style="padding:10px 10px 10px 10px">&nbsp Hash Values in Hexa-Decimal</td><td style="padding:3px">Bits <br>2<sup>2</sup> x Len</td> 
		<td style="padding:3px">Elapsed Time <br>in micro seconds</td><td style="text-align:center ">Collision<br>Probability 2<sup>-n/2</sup></tr>
<?php
    $algo_array = hash_algos();
	$max_time=max($time_array); //-min($time_array);
	$coll_diff=max($coll_array); //-min($coll_array);
	$percent = 0;
	foreach ($algo_array as $ha) {
		if ($max_time>0) {
			$time_percent = round(($time_array[$ha]/$max_time)*100);
		}
		if ($time_percent > 100) {
			$time_percent = 100;
		}
		$bits = strlen($hash_array[$ha])*4;
		
		$bits_string = ($bits==0) ? '' : $bits;
		
		$time_string = is_null($time_array[$ha]) ? '' : $time_array[$ha].' &#181;s';
		
		echo'<tr><td class="hash_label">'.$ha.'</td><td><div class="progress"><div class="bar_red" style="width:'.$time_percent.'%"></div ><div class="percent">'
		.$hash_array[$ha].'</div ></div></td> '.
			'<td class="compute">'.$bits_string.'</td> '.
			'<td class="compute" style="color:#561319">'.$time_string.'</td><td class="compute" style="padding-left:6px;color:green">'.$coll_array[$ha].'<td></tr> ';
			}
?>
		</table>
	</td></tr>
</table>

</body>
</html>