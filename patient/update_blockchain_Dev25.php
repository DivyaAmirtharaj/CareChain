<?php

// continue session
session_start();

//landing page definition -- where to go if something goes wrong
$landingpage = "index.php?site=".$_SESSION['site_id'];
//

// kick out if patient not authenticated
if (isset($_SESSION['pid']) && isset($_SESSION['patient_portal_onsite_two'])) {
    $pid = $_SESSION['pid'];
} else {
    session_destroy();
    header('Location: '.$landingpage.'&w');
    exit;
}

//

$ignoreAuth = 1;
global $ignoreAuth;

require_once("../interface/globals.php");
require_once("$srcdir/patient.inc");
require_once("$srcdir/forms.inc");

 // Exit if the modify calendar for portal flag is not set-pulled for v5
 /* if (!($GLOBALS['portal_onsite_appt_modify'])) {
   echo add_escape_custom( xl('You are not authorized to schedule appointments.'),ENT_NOQUOTES);
   exit;
 } */

 // Things that might be passed by our opener.
 //
 $eid           = $_GET['eid'];         // only for existing events
 $date          = $_GET['date'];        // this and below only for new events
 $blockchain_publickey      = $_GET['blockchain_publickey']; 
 $userid        = $_GET['userid'];
 $default_catid = $_GET['catid'] ? $_GET['catid'] : '5';
 $patientid     = $_GET['patid'];
 //


if ($_POST['form_action'] == "save") {
	
	sqlStatement("UPDATE patient_access_onsite SET blockchain_status = " . $_POST['form_status'] . 
	             ", blockchain_publickey = '" . $_POST['blockchain_publickey'] . "'" . 
				 ", blockchain_cloud = '". $_POST['blockchain_cloud']. "'" .
				 ", blockchain_cloud_username = '". $_POST['blockchain_cloud_username']. "'" .
				 " WHERE pid =" .  $_POST['form_pid']);
				 
    $_SESSION['whereto'] = 'blockchain';
    header('Location:./home.php');
}
if ($_POST['form_action'] == "regenerate") {
	
	$_SESSION['blockchain_publickey'] ="ccc";	
}

if ($_POST['form_action'] != "") {
  // Leave
    $_SESSION['whereto'] = 'blockchain';
    header('Location:./home.php');
    exit();
}

$row = array();


    $row = sqlQuery("SELECT blockchain_status, blockchain_publickey, blockchain_cloud, blockchain_cloud_username FROM patient_access_onsite WHERE pid = $pid");
    $blockchain_status = $row['blockchain_status'];
    $blockchain_publickey = $row['blockchain_publickey'];

    $patientid=$_GET['pid'];

//	echo $blockchain_status . '-' . $blockchain_publickey .'-'.$patientid
// If we have a patient ID, get the name and phone numbers to display.

// Get the providers list.
$ures = sqlStatement("SELECT id, username, fname, lname FROM users WHERE " .
    "authorized != 0 AND active = 1 ORDER BY lname, fname");

//-------------------------------------
//(CHEMED)
//Set default facility for a new event based on the given 'userid'
if ($userid) {
    $pref_facility = sqlFetchArray(sqlStatement("SELECT facility_id, facility FROM users WHERE id = $userid"));
    $e2f = $pref_facility['facility_id'];
    $e2f_name = $pref_facility['facility'];
}

 //END of CHEMED -----------------------

// Get event categories.
$cres = sqlStatement("SELECT pc_catid, pc_catname, pc_recurrtype, pc_duration, pc_end_all_day " .
"FROM openemr_postcalendar_categories ORDER BY pc_catname");

// Fix up the time format for AM/PM.
$startampm = '1';
if ($starttimeh >= 12) { // p.m. starts at noon and not 12:01
    $startampm = '2';
    if ($starttimeh > 12) {
        $starttimeh -= 12;
    }
}

?>
<html>
<head>
<?php //html_header_show(); ?>
<title><?php echo $eid ? "Edit" : "Add New" ?> <?php xl('Event', 'e');?></title>
<link href="assets/css/style.css?v=<?php echo $v_js_includes; ?>" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative']; ?>/jquery-datetimepicker-2-5-4/build/jquery.datetimepicker.min.css">

<script type="text/javascript" src="<?php echo $GLOBALS['assets_static_relative']; ?>/jquery-min-3-1-1/index.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['assets_static_relative']; ?>/jquery-datetimepicker-2-5-4/build/jquery.datetimepicker.full.min.js"></script>
<script type="text/javascript" src="../library/topdialog.js?v=<?php echo $v_js_includes; ?>"></script>
<script type="text/javascript" src="../library/dialog.js?v=<?php echo $v_js_includes; ?>"></script>
<script type="text/javascript" src="../library/textformat.js?v=<?php echo $v_js_includes; ?>"></script>
<script language="JavaScript" type="text/javascript" src="jsbn.js"></script>
<script language="JavaScript" type="text/javascript" src="random.js"></script>
<script language="JavaScript" type="text/javascript" src="hash.js"></script>
<script language="JavaScript" type="text/javascript" src="rsa.js"></script>
<script language="JavaScript" type="text/javascript" src="aes.js"></script>
<script language="JavaScript" type="text/javascript" src="api.js"></script>

</head>

<body class="body_top" >

<form method='post' name='blockchainform' id='blockchainform' action='update_blockchain.php?eid=<?php echo $pid ?>'>
<input type="hidden" name="form_action" id="form_action" value="">
   <input type='hidden' name='form_pid' id='form_pid' value='<?php echo $pid ?>' />
   <input type='hidden' name='form_status' id='form_status' value='<?php echo $row['blockchain_status']  ?>' />
   
<table border='0' width='100%'>
 <tr>
  <td width='10%' nowrap height="50"><b><?php xl('Blockchain', 'e'); ?>: </b></td>
  <td align="left"> <input type="checkbox" id='blockchain_status' name='blockchain_status' value='checked' checked='<?php echo $row['blockchain_status']==0 ? '' : "checked" ; ?>'/>
  </td>
 </tr>
 <tr>
  <td nowrap height="50"><b><?php xl('Public Key', 'e'); ?>: </b></td>
  <td align="left"><input type="text" id='blockchain_publickey' name='blockchain_publickey' size="50" height="3" rows="4" maxlength="300" value='<?php echo $row['blockchain_publickey']; ?>'/>
  <input type='button' name='form_regen' class='btn btn-active btn-sm' value='<?php xl('Regenerate', 'e');?>' onclick="regenerate()" />
  </td>
 </tr>
 <tr>
  <td nowrap height="50"><b><?php xl('Cloud Storage', 'e'); ?>: </b></td>
  <td align="left">
  <select id="blockchain_cloud" name="blockchain_cloud">                      
    <option value="">Select...</option>
    <option value="CareChain">CareChain</option>
    <option value="Google" selected="selected">Google Drive</option>
    <option value="Apple">iCloud Drive</option>
    <option value="Microsoft">One Drive</option>
    <option value="Dropbox">Dropbox</option>
  </select></td>
 </tr>
  <tr>
  <td nowrap height="50"><b><?php xl('Cloud Username', 'e'); ?>: </b></td>
  <td align="left"><input type="text" id='blockchain_cloud_username' name='blockchain_cloud_username' size="30" height="3" rows="4" maxlength="300" value='<?php echo $row['blockchain_cloud_username']; ?>'/></td>
 </tr>
 </table>
<p>
<input type='button' name='form_save' class='btn btn-success btn-md' value='<?php xl('Save', 'e');?>' onclick="blockchain_save()" />
&nbsp;
</p>
</form>
<script>

<?php // require($GLOBALS['srcdir'] . "/restoreSession.php"); ?>

 // Gray out certain fields according to selection of Category DDL
 function categoryChanged() {
    var value = '5';

    document.getElementById("form_patient").disabled=false;
    //document.getElementById("form_apptstatus").disabled=false;
    //document.getElementById("form_prefcat").disabled=false;
 }

function regenerate() {
  var f = document.forms.namedItem("blockchainform");	
  var form_action = document.getElementById('form_action');
  form_action.value="regenerate";
  var PassPhrase = "The Moon is a Harsh Mistress.";
  var Bits = 512;
        
        
        var MattsRSAkey = cryptico.generateRSAKey(PassPhrase, Bits);
        var MattsPublicKeyString = cryptico.publicKeyString(MattsRSAkey);       
         
  f.blockchain_publickey.value =  MattsPublicKeyString; 
//  f.submit();
}
function blockchain_save() {
  var f = document.forms.namedItem("blockchainform");
  var form_action = document.getElementById('form_action');
  form_action.value="save";
  if (f.blockchain_status.checked) {
	  f.form_status.value=1;
  }
  else {
	  f.form_status.value=0;
  }
  f.submit();
}

</script>

</body>
</html>
