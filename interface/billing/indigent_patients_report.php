<?php
/**
 * This is the Indigent Patients Report.  It displays a summary of
 * encounters within the specified time period for patients without
 * insurance.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Rod Roark <rod@sunsetsystems.com>
 * @author    Brady Miller <brady.g.miller@gmail.com>
 * @copyright Copyright (c) 2005-2015 Rod Roark <rod@sunsetsystems.com>
 * @copyright Copyright (c) 2017 Brady Miller <brady.g.miller@gmail.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

require_once("../globals.php");
require_once("$srcdir/patient.inc");

use OpenEMR\Core\Header;

$alertmsg = '';

function bucks($amount)
{
    if ($amount) {
        return oeFormatMoney($amount);
    }

    return "";
}

$form_start_date = (!empty($_POST['form_start_date'])) ?  DateToYYYYMMDD($_POST['form_start_date']) : date('Y-01-01');
$form_end_date  = (!empty($_POST['form_end_date'])) ? DateToYYYYMMDD($_POST['form_end_date']) : date('Y-m-d');

?>
<html>
<head>

<style type="text/css">

/* specifically include & exclude from printing */
@media print {
    #report_parameters {
        visibility: hidden;
        display: none;
    }
    #report_parameters_daterange {
        visibility: visible;
        display: inline;
    }
    #report_results table {
       margin-top: 0px;
    }
}

/* specifically exclude some from the screen */
@media screen {
    #report_parameters_daterange {
        visibility: hidden;
        display: none;
    }
}

</style>

<?php Header::setupHeader('datetime-picker'); ?>

<title><?php echo xlt('Indigent Patients Report')?></title>

<script language="JavaScript">

    $(document).ready(function() {
        var win = top.printLogSetup ? top : opener.top;
        win.printLogSetup(document.getElementById('printbutton'));

        $('.datepicker').datetimepicker({
            <?php $datetimepicker_timepicker = false; ?>
            <?php $datetimepicker_showseconds = false; ?>
            <?php $datetimepicker_formatInput = true; ?>
            <?php require($GLOBALS['srcdir'] . '/js/xl/jquery-datetimepicker-2-5-4.js.php'); ?>
            <?php // can add any additional javascript settings to datetimepicker here; need to prepend first setting with a comma ?>
        });
    });

</script>

</head>

<body class="body_top">

<span class='title'><?php echo xlt('Report'); ?> - <?php echo xlt('Indigent Patients'); ?></span>

<form method='post' action='indigent_patients_report.php' id='theform' onsubmit='return top.restoreSession()'>

<div id="report_parameters">

<input type='hidden' name='form_refresh' id='form_refresh' value=''/>

<table>
 <tr>
  <td width='410px'>
    <div style='float:left'>

    <table class='text'>
        <tr>
            <td class='control-label'>
                <?php echo xlt('Visits From'); ?>:
            </td>
            <td>
               <input type='text' class='datepicker form-control' name='form_start_date' id="form_start_date" size='10' value='<?php echo oeFormatShortDate($form_start_date); ?>'>
            </td>
            <td class='control-label'>
                <?php xl('To', 'e'); ?>:
            </td>
            <td>
               <input type='text' class='datepicker form-control' name='form_end_date' id="form_end_date" size='10' value='<?php echo oeFormatShortDate($form_end_date); ?>'>
            </td>
        </tr>
    </table>

    </div>

  </td>
  <td align='left' valign='middle' height="100%">
    <table style='border-left:1px solid; width:100%; height:100%' >
        <tr>
            <td>
                <div class="text-center">
          <div class="btn-group" role="group">
                      <a href='#' class='btn btn-default btn-save' onclick='$("#form_refresh").attr("value","true"); $("#theform").submit();'>
                            <?php echo xlt('Submit'); ?>
                      </a>
                        <?php if ($_POST['form_refresh']) { ?>
                        <a href='#' class='btn btn-default btn-print' id='printbutton'>
                                <?php echo xlt('Print'); ?>
                        </a>
                        <?php } ?>
          </div>
                </div>
            </td>
        </tr>
    </table>
  </td>
 </tr>
</table>
</div> <!-- end of parameters -->

<div id="report_results">
<table>

 <thead bgcolor="#dddddd">
  <th>
   &nbsp;<?php echo xlt('Patient'); ?>
  </th>
  <th>
   &nbsp;<?php echo xlt('SSN'); ?>
  </th>
  <th>
   &nbsp;<?php echo xlt('Invoice'); ?>
  </th>
  <th>
   &nbsp;<?php echo xlt('Svc Date'); ?>
  </th>
  <th>
   &nbsp;<?php echo xlt('Due Date'); ?>
  </th>
  <th align="right">
    <?php echo xlt('Amount'); ?>&nbsp;
  </th>
  <th align="right">
    <?php echo xlt('Paid'); ?>&nbsp;
  </th>
  <th align="right">
    <?php echo xlt('Balance'); ?>&nbsp;
  </th>
 </thead>

<?php
if ($_POST['form_refresh']) {
    $where = "";
    $sqlBindArray = array();

    if ($form_start_date) {
        $where .= " AND e.date >= ?";
        array_push($sqlBindArray, $form_start_date);
    }

    if ($form_end_date) {
        $where .= " AND e.date <= ?";
        array_push($sqlBindArray, $form_end_date);
    }

    $rez = sqlStatement("SELECT " .
    "e.date, e.encounter, p.pid, p.lname, p.fname, p.mname, p.ss " .
    "FROM form_encounter AS e, patient_data AS p, insurance_data AS i " .
    "WHERE p.pid = e.pid AND i.pid = e.pid AND i.type = 'primary' " .
    "AND i.provider = ''$where " .
    "ORDER BY p.lname, p.fname, p.mname, p.pid, e.date", $sqlBindArray);

    $total_amount = 0;
    $total_paid   = 0;

    for ($irow = 0; $row = sqlFetchArray($rez); ++$irow) {
        $patient_id = $row['pid'];
        $encounter_id = $row['encounter'];
        $invnumber = $row['pid'] . "." . $row['encounter'];
        $inv_duedate = '';
        $arow = sqlQuery("SELECT SUM(fee) AS amount FROM drug_sales WHERE " .
        "pid = ? AND encounter = ?", array($patient_id, $encounter_id));
        $inv_amount = $arow['amount'];
        $arow = sqlQuery("SELECT SUM(fee) AS amount FROM billing WHERE " .
          "pid = ? AND encounter = ? AND " .
          "activity = 1 AND code_type != 'COPAY'", array($patient_id, $encounter_id));
        $inv_amount += $arow['amount'];
        $arow = sqlQuery("SELECT SUM(fee) AS amount FROM billing WHERE " .
          "pid = ? AND encounter = ? AND " .
          "activity = 1 AND code_type = 'COPAY'", array($patient_id, $encounter_id));
        $inv_paid = 0 - $arow['amount'];
        $arow = sqlQuery("SELECT SUM(pay_amount) AS pay, " .
          "sum(adj_amount) AS adj FROM ar_activity WHERE " .
          "pid = ? AND encounter = ?", array($patient_id, $encounter_id));
        $inv_paid   += $arow['pay'];
        $inv_amount -= $arow['adj'];
        $total_amount += bucks($inv_amount);
        $total_paid   += bucks($inv_paid);

        $bgcolor = (($irow & 1) ? "#ffdddd" : "#ddddff");
    ?>
  <tr bgcolor='<?php  echo $bgcolor ?>'>
<td class="detail">
 &nbsp;<?php echo text($row['lname'] . ', ' . $row['fname'] . ' ' . $row['mname']); ?>
</td>
<td class="detail">
 &nbsp;<?php echo text($row['ss']); ?>
</td>
<td class="detail">
 &nbsp;<?php echo text($invnumber); ?></a>
</td>
<td class="detail">
 &nbsp;<?php echo oeFormatShortDate(substr($row['date'], 0, 10)); ?>
</td>
<td class="detail">
 &nbsp;<?php echo oeFormatShortDate($inv_duedate); ?>
</td>
<td class="detail" align="right">
    <?php echo bucks($inv_amount); ?>&nbsp;
</td>
<td class="detail" align="right">
    <?php echo bucks($inv_paid); ?>&nbsp;
</td>
<td class="detail" align="right">
    <?php echo bucks($inv_amount - $inv_paid); ?>&nbsp;
</td>
</tr>
<?php
    }
?>
<tr bgcolor='#dddddd'>
<td class="detail">
&nbsp;<?php echo xlt('Totals'); ?>
</td>
<td class="detail">
 &nbsp;
</td>
<td class="detail">
 &nbsp;
</td>
<td class="detail">
 &nbsp;
</td>
<td class="detail">
 &nbsp;
</td>
<td class="detail" align="right">
<?php echo bucks($total_amount); ?>&nbsp;
</td>
<td class="detail" align="right">
<?php echo bucks($total_paid); ?>&nbsp;
</td>
<td class="detail" align="right">
<?php echo bucks($total_amount - $total_paid); ?>&nbsp;
</td>
</tr>
<?php
}
?>

</table>
</div>

</form>
<script>
<?php
if ($alertmsg) {
    echo "alert('" . addslashes($alertmsg) . "');\n";
}
?>
</script>
</body>

</html>