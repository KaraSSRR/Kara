<?
$patch_project = $_SERVER['DOCUMENT_ROOT'];
$patch_global = $patch_project.'/inc/conf/global.php';
if(!empty($patch_global)){
    if(!file_exists($patch_global)){
        die('The problem with the connection files.');
    }else{
		require_once($patch_global);
    }
}
  $LombardList = Work::$sql->query("SELECT * FROM auk");
  if(!empty($LombardList->fetch_assoc())) {
    while($lombard = $LombardList->fetch_assoc()){
      $html .= '';
    }
  }else{
    $html = '<center>Аукцион не работает</center>';
  }

	$response['html'] = $html;
	$response['title'] = 'Аукционист';
	$response['type'] = 'lombard';
?>
