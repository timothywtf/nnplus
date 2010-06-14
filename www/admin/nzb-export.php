<?php

require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/util.php");

$db = new DB();

if (empty($argc))
	$page = new AdminPage();

$rel = new Releases();

if (!empty($argc) || $page->isPostBack() )
{
	$retval = "";	
	$strTerminator = "<br />";
	$postfrom = "";
	$postto = "";
	$group = "";
	$path = "";
	
	if (!empty($argc))
	{
		$strTerminator = "\n";
		if (isset($argv[1]))
			$path = $argv[1];
		if (isset($argv[2]))
			$postfrom = $argv[2];
		if (isset($argv[3]))
			$postto = $argv[3];
		if (isset($argv[4]))
			$group = $argv[4];
	}
	else		
	{
		$strTerminator = "<br />";
		$path = $_POST["folder"];
		if (isset($_POST["postfrom"]))
			$postfrom = $_POST["postfrom"];		
		if (isset($_POST["postto"]))
			$postto = $_POST["postto"];	
		if (isset($_POST["group"]))
			$group = $_POST["group"];				
	}

	if ($path != "")
	{
		if (substr($path, strlen($path) - 1) != '/')
			$path = $path."/";

		$releases = $rel->getForExport($postfrom, $postto, $group);
		$s = new Sites();
		$site = $s->get();
		$nzbCount = 0;
		
		foreach ($releases as $release)
		{
			ob_start();
			@readgzfile($site->nzbpath.$release["guid"].".nzb.gz");
			$nzbfile = ob_get_contents();
			ob_end_clean();
			$fh = fopen($path.safeFilename($release["searchname"]).".nzb", 'w');
			fwrite($fh, $nzbfile);
			fclose($fh);
			$nzbCount++;
		}
		
		$retval.= 'Processed '.$nzbCount.' nzbs';
	
		if (!empty($argc))
		{
			echo 'Processed '.$nzbCount.' nzbs';
			die();
		}
	}
	else
	{
		echo 'No export path specified.';
		die();
	}
	
	$page->smarty->assign('folder', $path);	
	$page->smarty->assign('output', $retval);	
	$page->smarty->assign('fromdate', $postfrom);	
	$page->smarty->assign('todate', $postto);	
	$page->smarty->assign('group', $group);	
	
}
else
{
	$page->smarty->assign('fromdate', $rel->getEarliestUsenetPostDate());	
	$page->smarty->assign('todate', $rel->getLatestUsenetPostDate());	
}

$page->title = "Export Nzbs";
$grouplist = $rel->getReleasedGroupsForSelect(true);
$page->smarty->assign('grouplist', $grouplist);
$page->content = $page->smarty->fetch('admin/nzb-export.tpl');
$page->render();

?>