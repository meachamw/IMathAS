<?php
//IMathAS:  Add/modify blocks of items on course page
//(c) 2006 David Lippman

/*** master php includes *******/
require("../validate.php");

/*** pre-html data manipulation, including function code *******/

//set some page specific variables and counters
$cid = $_GET['cid'];
$overwriteBody = 0;
$body = "";
$pagetitle = "Delete Inline Text";
$curBreadcrumb = "$breadcrumbbase <a href=\"course.php?cid=$cid\">$coursename</a> ";
$curBreadcrumb .= " &gt; Modify Inline Text\n";

if (!(isset($teacherid))) {  
	$overwriteBody = 1;
	$body = "You need to log in as a teacher to access this page";
} elseif (!(isset($_GET['cid']))) { 
	$overwriteBody = 1;
	$body = "You need to access this page from the link on the course page";
} elseif (isset($_GET['remove'])) { // a valid delete request loaded the page
	$cid = $_GET['cid'];
	$block = $_GET['block'];	
	if ($_GET['remove']=="really") {
		require("../includes/filehandler.php");
		$textid = $_GET['id'];
		
		$query = "SELECT id FROM imas_items WHERE typeid='$textid' AND itemtype='InlineText'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$itemid = mysql_fetch_first($result);
		
		$query = "DELETE FROM imas_items WHERE id='$itemid'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		
		$query = "DELETE FROM imas_inlinetext WHERE id='$textid'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		
		$query = "SELECT filename FROM imas_instr_files WHERE itemid='$textid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		//$uploaddir = rtrim(dirname(__FILE__), '/\\') .'/files/';
		while ($row = mysqli_fetch_row($result)) {
			$safefn = addslashes($row[0]);
			$query = "SELECT id FROM imas_instr_files WHERE filename='$safefn'";
			$r2 = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
			if (mysqli_num_rows($r2)==1) {
				//unlink($uploaddir . $row[0]);
				deletecoursefile($row[0]);
			}
		}
		$query = "DELETE FROM imas_instr_files WHERE itemid='$textid'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
					
		$query = "SELECT itemorder FROM imas_courses WHERE id='$cid'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$items = unserialize(mysql_fetch_first($result));
		
		$blocktree = explode('-',$block);
		$sub =& $items;
		for ($i=1;$i<count($blocktree);$i++) {
			$sub =& $sub[$blocktree[$i]-1]['items']; //-1 to adjust for 1-indexing
		}
		$key = array_search($itemid,$sub);
		array_splice($sub,$key,1);
		$itemorder = addslashes(serialize($items));
		$query = "UPDATE imas_courses SET itemorder='$itemorder' WHERE id='$cid'";
		mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		
		header('Location: ' . $urlmode  . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/course.php?cid=$cid");
		exit;
	} else {
		$query = "SELECT title FROM imas_inlinetext WHERE id='{$_GET['id']}'";
		$result = mysqli_query($GLOBALS['link'],$query) or die("Query failed : " . mysqli_error($GLOBALS['link']));
		$itemname = mysql_fetch_first($result);
	}
} 

/******* begin html output ********/
require("../header.php");

/**** post-html data manipulation ******/
// this page has no post-html data manipulation

/***** page body *****/
/***** php display blocks are interspersed throughout the html as needed ****/
if ($overwriteBody==1) {
	echo $body;
} else {
?>
	
<div class=breadcrumb><?php echo $curBreadcrumb; ?></div>
<h3><?php echo $itemname; ?></h3>
Are you SURE you want to delete this text item?
	<p><input type=button value="Yes, Remove" onClick="window.location='deleteinlinetext.php?cid=<?php echo $cid ?>&block=<?php echo $block ?>&id=<?php echo $_GET['id'] ?>&remove=really'">
	<input type=button value="Nevermind" onClick="window.location='course.php?cid=<?php echo $cid ?>'"></p>

<?php
}
	require("../footer.php");
?>
