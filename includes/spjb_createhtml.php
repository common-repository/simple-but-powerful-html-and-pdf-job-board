<?php

function createhtml($id, $type, $title, $content, $htmllink){
	$template = file_get_contents(__DIR__."/template.html");
	$template = str_replace("[title_placeholder]", stripslashes($title), $template);
	$template = str_replace("[content_placeholder]", stripslashes($content), $template);
	$template = str_replace("&nbsp;", "<br /><br />", $template);
	$file = fopen($htmllink, "w");
	fwrite($file, $template);
	fclose($file);
}

?>