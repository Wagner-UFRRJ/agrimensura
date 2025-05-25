<?PHP
	require("classes.php");
	$ponto1 = new PontoGeografico(30.9, 45.0, 10.0);
	$ponto2 = new PontoGeografico(30.9, 45.0, 10.0);
	$ponto3 = new PontoGeografico(30.9, 45.0, 10.0);
	$ponto4 = new PontoGeografico(30.9, 45.0, 10.0);
	echo $ponto1->getLatitude();
	echo "<br>";
	$ponto1->setLatitude(77.875);
	echo "<br>";
	echo $ponto1->getLatitude();
	echo "<br>";
	echo $ponto1->descrever();
	$ponto1->setLatitude(20.0);
	echo "<br>";
	echo $ponto1->descrever();
	echo "<br>";
	$ponto1->id=10;
	echo $ponto1->id;
	
	
?>