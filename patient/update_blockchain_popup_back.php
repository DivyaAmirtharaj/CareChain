<?php
/**
 *
 * Modified from interface/main/calendar/add_edit_event.php for
 * the patient portal.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Rod Roark <rod@sunsetsystems.com>
 * @author    Jerry Padgett <sjpadgett@gmail.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (C) 2005-2006 Rod Roark <rod@sunsetsystems.com>
 * @copyright Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 * @copyright Copyright (c) 2017 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

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
 $userid        = $_GET['userid'];
 $default_catid = $_GET['catid'] ? $_GET['catid'] : '5';
 $patientid     = $_GET['patid'];
 //


if ($_POST['form_action'] == "save") {
	
    $_SESSION['whereto'] = 'blockchainsetup';
//    header('Location:./profilereport.php');
 //   exit();
//        sqlStatement("DELETE FROM openemr_postcalendar_events WHERE pc_eid = '$eid'");
}

if ($_POST['form_action'] != "") {
  // Leave
    $_SESSION['whereto'] = 'blockchainsetup';
    header('Location:./home.php');
    exit();
}

$row = array();

// If we are editing an existing event, then get its data.

    $row = sqlQuery("SELECT blockchain_status, blockchain_publickey FROM patient_access_onsite WHERE pid = $pid");
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

</head>

<body class="body_top" >

<form method='post' name='theaddform' id='theaddform' action='update_blockchain.php?eid=<?php echo $eid ?>'>
<input type="hidden" name="form_action" id="form_action" value="">
   <input type='hidden' name='form_category' id='form_category' value='<?php echo $row['pc_catid'] ? $row['pc_catid'] : '5'; ?>' />
   <input type='hidden' name='form_apptstatus' id='form_apptstatus' value='<?php echo $row['pc_apptstatus'] ? $row['pc_apptstatus'] : "^" ?>' />
<table border='0' width='100%'>
 <tr>
  <td width='1%' nowrap>
   <b><?php xl('Blockchain', 'e'); ?>: </b>
  </td>
  <td nowrap style='padding:0px 5px 5px 0'>
   <input class="form-control input-sm" type="checkbox" id='form_title' name='form_title' value='checked'
   checked='<?php echo $row['blockchain_status']==0 ? '' : "checked" ; ?>'/>
  </td>
  <td></td>
  <td width='1%' nowrap>
    <b><?php xl('Date', 'e'); ?>:</b>
  </td>
  <td colspan='2' nowrap id='tdallday1'>
   <input class="form-control input-md" type='text' size='10' name='form_date' readonly id='form_date'
    value='<?php echo (isset($eid) && $eid) ? $row['pc_eventDate'] : $date; ?>'  />
  </td>
 </tr>
 <tr>
  <td nowrap>
   <b><?php //xl('Title','e'); ?></b>
  </td>
  <td style='padding:0px 5px 5px 0' nowrap>
   <!-- <input class="form-control input-md" type='text' size='10' name='form_title' readonly value='<?php //echo htmlspecialchars($row['pc_title'],ENT_QUOTES) ?>' title='<?php //xl('Event title','e'); ?>' /> -->
  </td>
  <td nowrap>
  </td>
  <td width='1%' nowrap id='tdallday2'>
   <b><?php xl('Time', 'e');?>:</b>
  </td>
  <td width='1%' nowrap id='tdallday3'>
   <input class="form-control inline" type='text' size='2' name='form_hour' value='<?php echo (isset($eid)) ? $starttimeh : ''; ?>'
    title='<?php xl('Event start time', 'e'); ?>' readonly/> :
  <input class="form-control inline" type='text' size='2' name='form_minute' value='<?php echo (isset($eid)) ? $starttimem : ''; ?>'
    title='<?php  xl('Event start time', 'e'); ?>' readonly/>&nbsp; <!--  -->
   <select class="form-control" name='form_ampm' title='Note: 12:00 noon is PM, not AM' readonly >
    <option value='1'><?php xl('AM', 'e'); ?></option>
    <option value='2'<?php echo ($startampm == '2') ? " selected" : ""; ?>><?php xl('PM', 'e'); ?></option>
   </select>
  </td>
 </tr>
 <tr>
  <td nowrap>
   <b><?php xl('Patient', 'e'); ?>:</b>
  </td>
  <td style='padding:0px 5px 5px 0' nowrap>
   <input class="form-control input-md" type='text' size='10' id='form_patient' name='form_patient' value='<?php echo $patientname ?>' title='Patient' readonly />
   <input name='form_pid' value='<?php echo $pid ?>' />
  </td>
  <td nowrap>
   &nbsp;
  </td>
  <td nowrap id='tdallday4'><?php xl('Duration', 'e'); ?></td>
  <td nowrap id='tdallday5'>
   <!-- --> <input class="form-control input-md" type='text' size='1' name='form_duration' value='<?php echo $row['pc_duration'] ? ($row['pc_duration']*1/60) : "0" ?>' readonly /><?php echo xl('minutes'); ?>
  </td>
 </tr>
    <tr>
    </tr>
 </table>
<p>
<input type='button' name='form_save' class='btn btn-success btn-md' onsubmit='return false' value='<?php xl('Save', 'e');?>' onclick="validate()" />
&nbsp;
</p>
</form>
<script>

 var durations = new Array();
 // var rectypes  = new Array();

<?php // require($GLOBALS['srcdir'] . "/restoreSession.php"); ?>

 // This is for callback by the find-patient popup.
 function setpatient(pid, lname, fname, dob) {
  var f = document.forms.namedItem("theaddform");
  f.form_patient.value = lname + ', ' + fname;
  f.form_pid.value = pid;
  dobstyle = (dob == '' || dob.substr(5, 10) == '00-00') ? '' : 'none';
  document.getElementById('dob_row').style.display = dobstyle;
 }
 function change_provider(){
  var f = document.forms.namedItem("theaddform");
  f.form_date.value='';
  f.form_hour.value='';
  f.form_minute.value='';
 }
 // This is for callback by the find-patient popup.
 function unsetpatient() {
  var f = document.forms.namedItem("theaddform");
  f.form_patient.value = '';
  f.form_pid.value = '';
 }

 // This invokes the find-patient popup.
 function sel_patient() {
  dlgopen('find_patient_popup.php', '_blank', 500, 400);
 }

 // Do whatever is needed when a new event category is selected.
 // For now this means changing the event title and duration.
 function set_display() {
  var f = document.forms.namedItem("theaddform");
  var si = document.getElementById('form_category');
  if (si.selectedIndex >= 0) {
   var catid = si.options[si.selectedIndex].value;
   var style_apptstatus = document.getElementById('title_apptstatus').style;
   var style_prefcat = document.getElementById('title_prefcat').style;
   if (catid == '2') { // In Office
    style_apptstatus.display = 'none';
    style_prefcat.display = '';
    f.form_apptstatus.style.display = 'none';
    f.form_prefcat.style.display = '';
   } else {
    style_prefcat.display = 'none';
    style_apptstatus.display = '';
    f.form_prefcat.style.display = 'none';
    f.form_apptstatus.style.display = '';
   }
  }
 }

 // Gray out certain fields according to selection of Category DDL
 function categoryChanged() {
    var value = '5';

    document.getElementById("form_patient").disabled=false;
    //document.getElementById("form_apptstatus").disabled=false;
    //document.getElementById("form_prefcat").disabled=false;

 }

 // Do whatever is needed when a new event category is selected.
 // For now this means changing the event title and duration.
 function set_category() {
  var f = document.forms.namedItem("theaddform");
  var s = f.form_category;
  if (s.selectedIndex >= 0) {
   var catid = s.options[s.selectedIndex].value;
   f.form_title.value = s.options[s.selectedIndex].text;
   f.form_duration.value = durations[catid];
   set_display();
  }
 }

 // Modify some visual attributes when the all-day or timed-event
 // radio buttons are clicked.
 function set_allday() {
  var f = document.forms.namedItem("theaddform");
  var color1 = '#777777';
  var color2 = '#777777';
  var disabled2 = true;
  /*if (document.getElementById('rballday1').checked) {
   color1 = '#000000';
  }
  if (document.getElementById('rballday2').checked) {
   color2 = '#000000';
   disabled2 = false;
  }*/
  document.getElementById('tdallday1').style.color = color1;
  document.getElementById('tdallday2').style.color = color2;
  document.getElementById('tdallday3').style.color = color2;
  document.getElementById('tdallday4').style.color = color2;
  document.getElementById('tdallday5').style.color = color2;
  f.form_hour.disabled     = disabled2;
  f.form_minute.disabled   = disabled2;
  f.form_ampm.disabled     = disabled2;
  f.form_duration.disabled = disabled2;
 }

 // Modify some visual attributes when the Repeat checkbox is clicked.
 function set_repeat() {
  var f = document.forms.namedItem("theaddform");
  var isdisabled = true;
  var mycolor = '#777777';
  var myvisibility = 'hidden';
  /*if (f.form_repeat.checked) {
   isdisabled = false;
   mycolor = '#000000';
   myvisibility = 'visible';
  }*/
  //f.form_repeat_type.disabled = isdisabled;
  //f.form_repeat_freq.disabled = isdisabled;
  //f.form_enddate.disabled = isdisabled;
  document.getElementById('tdrepeat1').style.color = mycolor;
  document.getElementById('tdrepeat2').style.color = mycolor;
  document.getElementById('img_enddate').style.visibility = myvisibility;
 }

 // This is for callback by the find-available popup.
 function setappt(year,mon,mday,hours,minutes) {
  var f = document.forms.namedItem("theaddform");
  f.form_date.value = '' + year + '-' +
   ('' + (mon  + 100)).substring(1) + '-' +
   ('' + (mday + 100)).substring(1);
  f.form_ampm.selectedIndex = (hours >= 12) ? 1 : 0;
  f.form_hour.value = (hours > 12) ? hours - 12 : hours;
  f.form_minute.value = ('' + (minutes + 100)).substring(1);
 }

    // Invoke the find-available popup.
    function find_available() {

        // (CHEMED) Conditional value selection, because there is no <select> element
        // when making an appointment for a specific provider
        var se = document.getElementById('form_provider_ae');
        <?php if ($userid != 0) { ?>
            s = se.value;
        <?php } else {?>
            s = se.options[se.selectedIndex].value;
        <?php }?>
        var formDate = document.getElementById('form_date');
         window.open('find_appt_popup_user.php?bypatient&providerid=' + s +
                '&catid=5' +
                '&startdate=' + formDate.value, '_blank', "width=900,height=800");
    }

 // Check for errors when the form is submitted.
function validate() {
	var f = document.forms.namedItem("theaddform");
  var form_action = document.getElementById('form_action');
  form_action.value="save";
  f.submit();
  return false;
//  var f = document.getElementById('theaddform');
 // alert('Value='.f.form_title.value );

}
/* function validate() {
  var f = document.getElementById('theaddform');
  
  alert('Inside validate ' .f.form_title.value );

  var form_action = document.getElementById('form_action');
  form_action.value="save";
  f.submit();
  return false;
 }
*/

<?php if ($eid) { ?>
 set_display();
<?php } ?>

    $(document).ready(function() {
        $('.datepicker').datetimepicker({
            <?php $datetimepicker_timepicker = false; ?>
            <?php $datetimepicker_showseconds = false; ?>
            <?php $datetimepicker_formatInput = false; ?>
            <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
            <?php // can add any additional javascript settings to datetimepicker here; need to prepend first setting with a comma ?>
        });
    });
</script>

</body>
</html>
