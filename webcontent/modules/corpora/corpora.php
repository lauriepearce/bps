<?php
//
// This should combine both an admin view and a browse view, depending upon
// the role of the logged in user. Can check for CorporaAdmin role.
// If not an admin, just lists the corpora with name and description.
// Should probably base this upon something like the adminRoles page.
// If an Admin, can make those widgets be editable. Else, just <p> elements.
// They link to the default workspace for each, to see stats, etc.
// Question: should we separate view and admin, so the view feature is
// the common one for all, and then support an admin link that has the alternate
// UI? Could make it the same page, with a different option. I think yes.

/* Include Files *********************/
require_once("../../libs/env.php");
require_once("../admin/authUtils.php");
// require_once("HTTP/Request2.php");
require_once "../../libs/RESTClient.php";
/*************************************/

// If the user isn't logged in, send to the login page.
// FIXME - allow not logged in state - will be all no-edit
if(($login_state != BPS_LOGGED_IN) && ($login_state != BPS_REG_PENDING)){
	header( 'Location: ' . $CFG->wwwroot .
					'/login?redir=' .$_SERVER['REQUEST_URI'] );
	die();
}

$t->assign('page_title', 'Corpora Management'.$CFG->page_title_default);

$canAddCorpus = false;
$canUpdateCorpus = false;
$canDeleteCorpus = false;
if(currUserHasPerm( 'CorpusAdd' )) {
	$canAddCorpus = true;
	$t->assign('canAddCorpus', 1);
}
if(currUserHasPerm( 'CorpusUpdate' )) {
	$canUpdateCorpus = true;
	$t->assign('canUpdateCorpus', 1);
}
if(currUserHasPerm( 'CorpusDelete' )) {
	$canDeleteCorpus = true;
	$t->assign('canDeleteCorpus', 1);
}

$style_block = "<style>
td.title { border-bottom: 2px solid black; font-weight:bold; text-align:left; 
		font-style:italic; color:#777777; }
td.corpus_label { font-weight:bold; color:#61615f; }
td.corpusname { font-weight:bold; }
td.corpusdesc p { font-weight:bold; }
td.corpusndocs { text-align:right; padding-right:10px;}
td.corpusX { border-bottom: 1px solid black; }
td.corpusdesc textarea { font-family: Arial, Helvetica, sans-serif; padding:2px;}
form.form_row  { padding:0px; margin:2px;}
div.form_row  { padding:5px 0px 5px 0px; border-bottom: 1px solid black; }
</style>";

$t->assign("style_block", $style_block);

$themebase = $CFG->wwwroot.'/themes/'.$CFG->theme;

$script_block = '
<script type="text/javascript" src="/scripts/setupXMLHttpObj.js"></script>
<script type="text/javascript" src="/scripts/corpus.js"></script>
<script>

var curr_user_id = '.$_SESSION['id'].';
	
// The ready state change callback method that waits for a response.
function addCorpusRSC() {
  if (xmlhttp.readyState==4) {
		if( xmlhttp.status == 201 ) {
			// Maybe this should change the cursor or something
			window.status = "Corpus added.";
			window.location.reload();
	    //alert( "Response: " + xmlhttp.status + " Body: " + xmlhttp.responseText );
		} else {
			alert( "Error encountered when trying to add corpus.\nResponse: "
			 				+ xmlhttp.status + "\nBody: " + xmlhttp.responseText );
		}
	}
}

function addCorpus() {
	var name = document.getElementById("newCorpusName").value;
	var desc = document.getElementById("newCorpusDesc").value;
	if( desc.length <= 2 ) {
		alert( "You must enter a description that is at least 3 characters long" );
		return;
	}
	if( !xmlhttp ) {
		alert( "Cannot add corpus - no http obj!\n Please advise BPS support." );
		return;
	}
	var url = "'.$CFG->svcsbase.'/corpora";
	//var args = "owner="+curr_user_id+"&name="+name+"&description="+desc;
	var args = prepareCorpusXML("", name, desc, curr_user_id);
	//alert( "Preparing request: POST: "+url+"?"+args );
	xmlhttp.open("POST", url, true);
	xmlhttp.setRequestHeader("Content-Type","application/xml" );
	xmlhttp.onreadystatechange=addCorpusRSC;
	xmlhttp.send(args);
	//window.status = "request sent: POST: "+url+"?"+args;
	var el = document.getElementById("addCorpButton");
	el.disabled = true;
}

// The ready state change callback method that waits for a response.
function deleteCorpusRSC() {
  if (xmlhttp.readyState==4) {
		if( xmlhttp.status == 200 ) {
			// Maybe this should change the cursor or something
			window.status = "Corpus deleted.";
			window.location.reload();
	    //alert( "Response: " + xmlhttp.status + " Body: " + xmlhttp.responseText );
		} else {
			alert( "Error encountered when trying to delete corpus.\nResponse: "
			 				+ xmlhttp.status + "\nBody: " + xmlhttp.responseText );
		}
	}
}

function deleteCorpus(id) {
	if( !xmlhttp ) {
		alert( "Cannot delete corpus - no http obj!\n Please advise BPS support." );
		return;
	}
	var url = "'.$CFG->svcsbase.'/corpora/"+id;
	//alert( "Preparing request: DELETE: "+url);
	xmlhttp.open("DELETE", url, true);
	xmlhttp.setRequestHeader("Content-Type",
														"application/x-www-form-urlencoded" );
	xmlhttp.onreadystatechange=deleteCorpusRSC;
	xmlhttp.send(null);
	//window.status = "request sent: POST: "+url+"?"+args;
	var el = document.getElementById("deleteCorpButton_"+id);
	if(el!=null)
		el.disabled = true;
}

// This should go into a utils.js - how to include?
function enableElement( elID ) {
	//alert( "enableElement" );
	var el = document.getElementById(elID);
	el.disabled = false;
	//window.status = "Element ["+elID+"] enabled.";
}

</script>';

$t->assign("script_block", $script_block);

/**********************************
 HANDLE UPDATE AND DELETE
**********************************/
if(isset($_POST['delete'])){
	if(empty($_POST['id'])) {
		$opmsg = "Problem deleting corpus (no ID specified).";
	} else if(!$canDeleteCorpus) {
		$opmsg = "You do not have rights to delete a corpus.";
	} else {
		$corpusID = $_POST['id'];
		// FIXME need to move to prepared statements and params
		$deleteQ = "DELETE FROM corpus WHERE id=?";
		$stmt = $db->prepare($deleteQ, array('integer'), MDB2_PREPARE_MANIP);
		$res =& $stmt->execute($corpusID);
		if (PEAR::isError($res)) {
			$opmsg = "Problem deleting corpus.<br />".$res->getMessage();
		} else {
			$opmsg = "Corpus deleted.";
		}
		$stmt->free();
	}
}
else if(isset($_POST['add'])){
	unset($errmsg);
	$corpusname = "";
	$corpusdesc = "";
	if(!$canAddCorpus) {
		$errmsg = "You do not have rights to add a corpus.";
	} else {
		// FIXME need to move to prepared statements and params
		$corpusname = trim($_POST['corpname']);
		if( strlen( $corpusname ) < 4 )
			$errmsg = "Invalid corpus name: [".$corpusname."]";
		else if( preg_match( "/[^\w\s]/", $corpusname ))
			$errmsg = "Invalid corpus name (invalid chars): [".$corpusname."]";
		else if(empty($_POST['desc']))
			$errmsg = "Missing corpus description.";
		else {
			$corpusdesc = trim($_POST['desc']);
			if( strlen( $corpusdesc ) > 255 )
				$errmsg = "Invalid corpus description (too long);";
			else if( preg_match( "/[^\w\-\s.:'()]/", $corpusdesc ))
				$errmsg = "Invalid corpus description (invalid chars): [".$corpusdesc."]";
		}
	}
	if(!empty($errmsg))
		$opmsg = $errmsg;
	else {
		$user_id = $_SESSION['id'];
		$addQ = "INSERT IGNORE INTO corpus(name, description, owner_id, creation_time)"
			." VALUES (?,?,?, now())";
		$stmt = $db->prepare($addQ, array('text','text','integer'), MDB2_PREPARE_MANIP);
		$res =& $stmt->execute(array($corpusdesc,$corpusdesc,$user_id));
		if (PEAR::isError($res)) {
			$opmsg = "Problem adding corpus \"".$corpusname."\".<br />".$res->getMessage();
		} else {
			$opmsg = "Corpus \"".$corpusname."\" added.";
		}
	}
}

/**********************************
GET ALL CORPORA IN SYSTEM
**********************************/

function getCorpora(){
	global $db;
	// Get all the corpora, with doc counts, and order by when added
	$sql = 	'	SELECT c.id, c.name, c.description, count(*) nDocs, d.id docid'
				 .' FROM corpus c LEFT JOIN document d ON d.corpus_id=c.id GROUP BY c.id'
				 .' ORDER BY c.creation_time';
	$res =& $db->query($sql);
	if (PEAR::isError($res)) {
		// FIXME when debugged, comment this out and just return false
    die( 'Error in sql ['.$sql.']to getCorpora: '.$res->getMessage());
		// return false;
	}
	$corpora = array();
	while ($row = $res->fetchRow()) {
		$nDocs = 0 + $row['nDocs'];
		if(( $nDocs == 1 ) && empty($row['docid'])) {
			$nDocs = 0;
		}
		$corpus = array(	'id' => $row['id'], 'name' => $row['name'], 
						'nDocs' => $nDocs, 'description' => $row['description']);
		
		array_push($corpora, $corpus);
	}
	// Free the result
	$res->free();
	return $corpora;
}

$corpora = getCorpora();
if($corpora){
	$t->assign('corpora', $corpora);
	$rest = new RESTclient();
	$url = $CFG->wwwroot.$CFG->svcsbase."/corpora/";
	$rest->createRequest($url,"GET");
	// Enable this after editing the resource to allow both.
	// $rest->setJSONMode();
	if($rest->sendRequest()) {
		$ServCorpOutput = $rest->getResponse();
	} else {
		$ServCorpOutput = $rest->getError();
	}
}


if($opmsg!="")
	$t->assign('opmsg', $opmsg);
else if($ServCorpOutput != "")
	$t->assign('opmsg', $ServCorpOutput);

$t->display('corpora.tpl');

?>






















