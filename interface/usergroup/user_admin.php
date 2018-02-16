<?php
/**
 * Edit user.
 *
 * @package OpenEMR
 * @link    http://www.open-emr.org
 * @author  Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2017 Brady Miller <brady.g.miller@gmail.com>
 * @license https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../globals.php");
require_once("../../library/acl.inc");
require_once("$srcdir/calendar.inc");
require_once("$srcdir/options.inc.php");
require_once("$srcdir/erx_javascript.inc.php");

use OpenEMR\Menu\MainMenuRole;
use OpenEMR\Services\FacilityService;

$facilityService = new FacilityService();

if (!$_GET["id"] || !acl_check('admin', 'users')) {
    exit();
}

$res = sqlStatement("select * from users where id=?", array($_GET["id"]));
for ($iter = 0; $row = sqlFetchArray($res); $iter++) {
                $result[$iter] = $row;
}

$iter = $result[0];

?>

<html>
<head>

<link rel="stylesheet" href="<?php echo $css_header; ?>" type="text/css">
<script type="text/javascript" src="../../library/dialog.js?v=<?php echo $v_js_includes; ?>"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['assets_static_relative'] ?>/jquery-min-1-9-1/index.js"></script>
<script type="text/javascript" src="../../library/js/common.js"></script>

<script src="checkpwd_validation.js" type="text/javascript"></script>

<!-- validation library -->
<!--//Not lbf forms use the new validation, please make sure you have the corresponding values in the list Page validation-->
<?php    $use_validate_js = 1;?>
<?php  require_once($GLOBALS['srcdir'] . "/validation/validation_script.js.php"); ?>
<?php
//Gets validation rules from Page Validation list.
//Note that for technical reasons, we are bypassing the standard validateUsingPageRules() call.
$collectthis = collectValidationPageRules("/interface/usergroup/user_admin.php");
if (empty($collectthis)) {
    $collectthis = "undefined";
} else {
    $collectthis = $collectthis["user_form"]["rules"];
}
?>

<script language="JavaScript">

/*
 * validation on the form with new client side validation (using validate.js).
 * this enable to add new rules for this form in the pageValidation list.
 * */
var collectvalidation = <?php echo($collectthis); ?>;

    function checkChange()
{
  alert("<?php echo addslashes(xl('If you change e-RX Role for ePrescription, it may affect the ePrescription workflow. If you face any difficulty, contact your ePrescription vendor.'));?>");
}
function submitform() {

    var valid = submitme(1, undefined, 'user_form', collectvalidation);
    if (!valid) return;

    top.restoreSession();
    var flag=0;
    <?php if (!$GLOBALS['use_active_directory']) { ?>
    if(document.forms[0].clearPass.value!="")
    {
        //Checking for the strong password if the 'secure password' feature is enabled
        if(document.forms[0].secure_pwd.value == 1)
        {
                    var pwdresult = passwordvalidate(document.forms[0].clearPass.value);
                    if(pwdresult == 0) {
                            flag=1;
                            alert("<?php echo xl('The password must be at least eight characters, and should');
                            echo '\n';
                            echo xl('contain at least three of the four following items:');
                            echo '\n';
                            echo xl('A number');
                            echo '\n';
                            echo xl('A lowercase letter');
                            echo '\n';
                            echo xl('An uppercase letter');
                            echo '\n';
                            echo xl('A special character');
                            echo '(';
                            echo xl('not a letter or number');
                            echo ').';
                            echo '\n';
                            echo xl('For example:');
                            echo ' healthCare@09'; ?>");
                            return false;
                    }
        }

    }//If pwd null ends here
    <?php } ?>
    //Request to reset the user password if the user was deactived once the password expired.
    if((document.forms[0].pwd_expires.value != 0) && (document.forms[0].clearPass.value == "")) {
        if((document.forms[0].user_type.value != "Emergency Login") && (document.forms[0].pre_active.value == 0) && (document.forms[0].active.checked == 1) && (document.forms[0].grace_time.value != "") && (document.forms[0].current_date.value) > (document.forms[0].grace_time.value))
        {
            flag=1;
            document.getElementById('error_message').innerHTML="<?php xl('Please reset the password.', 'e') ?>";
        }
    }

  if (document.forms[0].access_group_id) {
    var sel = getSelected(document.forms[0].access_group_id.options);
    for (var item in sel) {
      if (sel[item].value == "Emergency Login") {
        document.forms[0].check_acl.value = 1;
      }
    }
  }

        <?php if ($GLOBALS['erx_enable']) { ?>
    alertMsg='';
    f=document.forms[0];
    for(i=0;i<f.length;i++){
      if(f[i].type=='text' && f[i].value)
      {
        if(f[i].name == 'fname' || f[i].name == 'mname' || f[i].name == 'lname')
        {
          alertMsg += checkLength(f[i].name,f[i].value,35);
          alertMsg += checkUsername(f[i].name,f[i].value);
        }
        else if(f[i].name == 'taxid')
        {
          alertMsg += checkLength(f[i].name,f[i].value,10);
          alertMsg += checkFederalEin(f[i].name,f[i].value);
        }
        else if(f[i].name == 'state_license_number')
        {
          alertMsg += checkLength(f[i].name,f[i].value,10);
          alertMsg += checkStateLicenseNumber(f[i].name,f[i].value);
        }
        else if(f[i].name == 'npi')
        {
          alertMsg += checkLength(f[i].name,f[i].value,10);
          alertMsg += checkTaxNpiDea(f[i].name,f[i].value);
        }
        else if(f[i].name == 'drugid')
        {
          alertMsg += checkLength(f[i].name,f[i].value,30);
          alertMsg += checkAlphaNumeric(f[i].name,f[i].value);
        }
      }
    }
    if(alertMsg)
    {
      alert(alertMsg);
      return false;
    }
    <?php } ?>
    if(flag == 0){
                    document.forms[0].submit();
                    parent.$.fn.fancybox.close();
    }
}
//Getting the list of selected item in ACL
function getSelected(opt) {
         var selected = new Array();
            var index = 0;
            for (var intLoop = 0; intLoop < opt.length; intLoop++) {
               if ((opt[intLoop].selected) ||
                   (opt[intLoop].checked)) {
                  index = selected.length;
                  selected[index] = new Object;
                  selected[index].value = opt[intLoop].value;
                  selected[index].index = intLoop;
               }
            }
            return selected;
         }

function authorized_clicked() {
 var f = document.forms[0];
 f.calendar.disabled = !f.authorized.checked;
 f.calendar.checked  =  f.authorized.checked;
}

</script>
<style type="text/css">
  .physician_type_class{
    width: 150px !important;
  }
</style>
</head>
<body class="body_top">
<table><tr><td>
<span class="title"><?php xl('Edit User', 'e'); ?></span>&nbsp;
</td><td>
    <a class="css_button" name='form_save' id='form_save' href='#' onclick='return submitform()'> <span><?php xl('Save', 'e');?></span> </a>
    <a class="css_button" id='cancel' href='#'><span><?php xl('Cancel', 'e');?></span></a>
</td></tr>
</table>
<br>
<FORM NAME="user_form" id="user_form" METHOD="POST" ACTION="usergroup_admin.php" target="_parent" onsubmit='return top.restoreSession()'>

<input type=hidden name="pwd_expires" value="<?php echo $GLOBALS['password_expiration_days']; ?>" >
<input type=hidden name="pre_active" value="<?php echo $iter["active"]; ?>" >
<input type=hidden name="exp_date" value="<?php echo $iter["pwd_expiration_date"]; ?>" >
<input type=hidden name="get_admin_id" value="<?php echo $GLOBALS['Emergency_Login_email']; ?>" >
<input type=hidden name="admin_id" value="<?php echo $GLOBALS['Emergency_Login_email_id']; ?>" >
<input type=hidden name="check_acl" value="">
<?php
//Calculating the grace time
$current_date = date("Y-m-d");
$password_exp=$iter["pwd_expiration_date"];
if ($password_exp != "0000-00-00") {
    $grace_time1 = date("Y-m-d", strtotime($password_exp . "+".$GLOBALS['password_grace_time'] ."days"));
}
?>
<input type=hidden name="current_date" value="<?php echo strtotime($current_date); ?>" >
<input type=hidden name="grace_time" value="<?php echo strtotime($grace_time1); ?>" >
<!--  Get the list ACL for the user -->
<?php
$acl_name=acl_get_group_titles($iter["username"]);
$bg_name='';
$bg_count=count($acl_name);
for ($i=0; $i<$bg_count; $i++) {
    if ($acl_name[$i] == "Emergency Login") {
        $bg_name=$acl_name[$i];
    }
}
?>
<input type=hidden name="user_type" value="<?php echo $bg_name; ?>" >

<TABLE border=0 cellpadding=0 cellspacing=0>
<TR>
    <TD style="width:180px;"><span class=text><?php xl('Username', 'e'); ?>: </span></TD>
    <TD style="width:270px;"><input type=entry name=username style="width:150px;" value="<?php echo $iter["username"]; ?>" disabled></td>
    <?php if (!$GLOBALS['use_active_directory']) { ?>
        <TD style="width:200px;"><span class=text><?php xl('Your Password', 'e'); ?>: </span></TD>
        <TD class='text' style="width:280px;"><input type='password' name=adminPass style="width:150px;"  value="" autocomplete='off'><font class="mandatory">*</font></TD>
    <?php } ?>
</TR>
    <?php if (!$GLOBALS['use_active_directory']) { ?>
<TR>
    <TD style="width:180px;"><span class=text></span></TD>
    <TD style="width:270px;"></td>
    <TD style="width:200px;"><span class=text><?php xl('User\'s New Password', 'e'); ?>: </span></TD>
    <TD class='text' style="width:280px;">    <input type=text name=clearPass style="width:150px;"  value=""><font class="mandatory">*</font></td>
</TR>
    <?php } ?>

<TR height="30" style="valign:middle;">
<td><span class="text">&nbsp;</span></td><td>&nbsp;</td>
<td colspan="2"><span class=text><?php xl('Provider', 'e'); ?>:
 <input type="checkbox" name="authorized" onclick="authorized_clicked()"<?php
    if ($iter["authorized"]) {
        echo " checked";
    } ?> />
 &nbsp;&nbsp;<span class='text'><?php xl('Calendar', 'e'); ?>:
 <input type="checkbox" name="calendar"<?php
    if ($iter["calendar"]) {
        echo " checked";
    }

    if (!$iter["authorized"]) {
        echo " disabled";
    } ?> />
 &nbsp;&nbsp;<span class='text'><?php xl('Active', 'e'); ?>:
 <input type="checkbox" name="active"<?php if ($iter["active"]) {
        echo " checked";
} ?> />
</TD>
</TR>

<TR>
<TD><span class=text><?php xl('First Name', 'e'); ?>: </span></TD>
<TD><input type=entry name=fname id=fname style="width:150px;" value="<?php echo $iter["fname"]; ?>"><span class="mandatory">&nbsp;*</span></td>
<td><span class=text><?php xl('Middle Name', 'e'); ?>: </span></TD><td><input type=entry name=mname style="width:150px;"  value="<?php echo $iter["mname"]; ?>"></td>
</TR>

<TR>
<td><span class=text><?php xl('Last Name', 'e'); ?>: </span></td><td><input type=entry name=lname id=lname style="width:150px;"  value="<?php echo $iter["lname"]; ?>"><span class="mandatory">&nbsp;*</span></td>
<td><span class=text><?php xl('Default Facility', 'e'); ?>: </span></td><td><select name=facility_id style="width:150px;" >
<?php
$fres = $facilityService->getAllBillingLocations();
if ($fres) {
    for ($iter2 = 0; $iter2 < sizeof($fres); $iter2++) {
                $result[$iter2] = $fres[$iter2];
    }

    foreach ($result as $iter2) {
        ?>
          <option value="<?php echo $iter2['id']; ?>" <?php if ($iter['facility_id'] == $iter2['id']) {
                echo "selected";
} ?>><?php echo htmlspecialchars($iter2['name']); ?></option>
<?php
    }
}
?>
</select></td>
</tr>

<?php if ($GLOBALS['restrict_user_facility']) { ?>
<tr>
 <td colspan=2>&nbsp;</td>
 <td><span class=text><?php xl('Schedule Facilities:', 'e');?></td>
 <td>
  <select name="schedule_facility[]" multiple style="width:150px;" >
<?php
  $userFacilities = getUserFacilities($_GET['id']);
  $ufid = array();
foreach ($userFacilities as $uf) {
    $ufid[] = $uf['id'];
}

  $fres = $facilityService->getAllServiceLocations();
if ($fres) {
    foreach ($fres as $frow) :
?>
   <option <?php echo in_array($frow['id'], $ufid) || $frow['id'] == $iter['facility_id'] ? "selected" : null ?>
      value="<?php echo $frow['id'] ?>"><?php echo htmlspecialchars($frow['name']) ?></option>
<?php
    endforeach;
}
?>
  </select>
 </td>
</tr>
<?php } ?>

<TR>
<TD><span class=text><?php xl('Federal Tax ID', 'e'); ?>: </span></TD><TD><input type=text name=taxid style="width:150px;"  value="<?php echo $iter["federaltaxid"]?>"></td>
<TD><span class=text><?php xl('Federal Drug ID', 'e'); ?>: </span></TD><TD><input type=text name=drugid style="width:150px;"  value="<?php echo $iter["federaldrugid"]?>"></td>
</TR>

<tr>
<td><span class="text"><?php xl('UPIN', 'e'); ?>: </span></td><td><input type="text" name="upin" style="width:150px;" value="<?php echo $iter["upin"]?>"></td>
<td class='text'><?php xl('See Authorizations', 'e'); ?>: </td>
<td><select name="see_auth" style="width:150px;" >
<?php
foreach (array(1 => xl('None'), 2 => xl('Only Mine'), 3 => xl('All')) as $key => $value) {
    echo " <option value='$key'";
    if ($key == $iter['see_auth']) {
        echo " selected";
    }

    echo ">$value</option>\n";
}
?>
</select></td>
</tr>

<tr>
<td><span class="text"><?php xl('NPI', 'e'); ?>: </span></td><td><input type="text" name="npi" style="width:150px;"  value="<?php echo $iter["npi"]?>"></td>
<td><span class="text"><?php xl('Job Description', 'e'); ?>: </span></td><td><input type="text" name="job" style="width:150px;"  value="<?php echo $iter["specialty"]?>"></td>
</tr>

<tr>
<td><span class="text"><?php xl('Taxonomy', 'e'); ?>: </span></td>
<td><input type="text" name="taxonomy" style="width:150px;"  value="<?php echo $iter["taxonomy"]?>"></td>
<td>&nbsp;</td><td>&nbsp;</td></tr>

<tr>
<td><span class="text"><?php xl('State License Number', 'e'); ?>: </span></td>
<td><input type="text" name="state_license_number" style="width:150px;"  value="<?php echo $iter["state_license_number"]?>"></td>
<td class='text'><?php xl('NewCrop eRX Role', 'e'); ?>:</td>
<td>
    <?php echo generate_select_list("erxrole", "newcrop_erx_role", $iter['newcrop_user_role'], '', xl('Select Role'), '', '', '', array('style'=>'width:150px')); ?>
</td>
</tr>
<tr>
<td><span class="text"><?php echo xlt('Weno Provider ID'); ?>: </span></td><td><input type="text" name="erxprid" style="width:150px;"  value="<?php echo attr($iter["weno_prov_id"]); ?>"></td>
</tr>

<tr>
  <td><span class="text"><?php xl('Provider Type', 'e'); ?>: </span></td>
  <td><?php echo generate_select_list("physician_type", "physician_type", $iter['physician_type'], '', xl('Select Type'), 'physician_type_class', '', '', ''); ?></td>
  <td>
    <span class="text"><?php echo xlt('Main Menu Role'); ?>: </span>
  </td>
  <td>
    <?php echo MainMenuRole::displayMainMenuRoleSelector($iter["main_menu_role"]); ?>
  </td>
</tr>
<?php if ($GLOBALS['inhouse_pharmacy']) { ?>
<tr>
 <td class="text"><?php xl('Default Warehouse', 'e'); ?>: </td>
 <td class='text'>
<?php
echo generate_select_list(
    'default_warehouse',
    'warehouse',
    $iter['default_warehouse'],
    ''
);
?>
 </td>
 <td class="text"><?php xl('Invoice Refno Pool', 'e'); ?>: </td>
 <td class='text'>
<?php
echo generate_select_list(
    'irnpool',
    'irnpool',
    $iter['irnpool'],
    xl('Invoice reference number pool, if used')
);
?>
 </td>
</tr>
<?php } ?>

<?php
 // Collect the access control group of user
if (isset($phpgacl_location) && acl_check('admin', 'acl')) {
?>
 <tr>
<td class='text'><?php xl('Access Control', 'e'); ?>:</td>
 <td><select id="access_group_id" name="access_group[]" multiple style="width:150px;" >
<?php
  $list_acl_groups = acl_get_group_title_list();
  $username_acl_groups = acl_get_group_titles($iter["username"]);
foreach ($list_acl_groups as $value) {
    if (($username_acl_groups) && in_array($value, $username_acl_groups)) {
        // Modified 6-2009 by BM - Translate group name if applicable
        echo " <option value='$value' selected>" . xl_gacl_group($value) . "</option>\n";
    } else {
        // Modified 6-2009 by BM - Translate group name if applicable
        echo " <option value='$value'>" . xl_gacl_group($value) . "</option>\n";
    }
}
    ?>
  </select></td>
  <td><span class=text><?php xl('Additional Info', 'e'); ?>:</span></td>
  <td><textarea style="width:150px;" name="comments" wrap=auto rows=4 cols=25><?php echo $iter["info"];?></textarea></td>

  </tr>
  <tr height="20" valign="bottom">
  <td colspan="4" class="text">
  <font class="mandatory">*</font> <?php xl('You must enter your own password to change user passwords. Leave blank to keep password unchanged.', 'e'); ?>
<!--
Display red alert if entered password matched one of last three passwords/Display red alert if user password was expired and the user was inactivated previously
-->
  <div class="redtext" id="error_message">&nbsp;</div>
  </td>
  </tr>
<?php
}
?>
</table>

<INPUT TYPE="HIDDEN" NAME="id" VALUE="<?php echo attr($_GET["id"]); ?>">
<INPUT TYPE="HIDDEN" NAME="mode" VALUE="update">
<INPUT TYPE="HIDDEN" NAME="privatemode" VALUE="user_admin">

<INPUT TYPE="HIDDEN" NAME="secure_pwd" VALUE="<?php echo $GLOBALS['secure_password']; ?>">
</FORM>
<script language="JavaScript">
$(document).ready(function(){
    $("#cancel").click(function() {
          parent.$.fn.fancybox.close();
     });

});
</script>
</BODY>

</HTML>

<?php
//  d41d8cd98f00b204e9800998ecf8427e == blank
?>