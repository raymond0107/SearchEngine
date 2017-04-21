<?php
	header('Content-type: application/json');
	if (isset($_GET["url"])) {
		$url = $_GET["url"];
		$content = file_get_contents($url);
		echo $content;
	}
?>