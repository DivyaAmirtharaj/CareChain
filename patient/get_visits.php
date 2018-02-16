<?php
/**
 *
 * Copyright (C) 2016-2017 Jerry Padgett <sjpadgett@gmail.com>
 * Copyright (C) 2011 Cassian LUP <cassi.lup@gmail.com>
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * @package OpenEMR
 * @author Cassian LUP <cassi.lup@gmail.com>
 * @author Jerry Padgett <sjpadgett@gmail.com>
 * @link http://www.open-emr.org
 *
 */
        require_once("verify_session.php");

        $sql = "select visit_date, reason, provider_name, facility, encounter from visits where pid=? order by encounter desc, sort_ord";

    $res = sqlStatement($sql, array($pid));

if (sqlNumRows($res)>0) {
    ?>
    <table class="table table-striped" style="font-size:8">
        <tr>
        <th><?php echo xlt('Date'); ?></th>
        <th><?php echo xlt('Visit / Encounter'); ?></th>
        <th><?php echo xlt('Provider'); ?></th>
        <th><?php echo xlt('Facility'); ?></th>
        <th><?php echo xlt('ID'); ?></th>
        </tr>
    <?php
    $even=false;
    while ($row = sqlFetchArray($res)) {
    	if ($row['visit_date'] == ' ') {
        echo "<tr class='".text($class)."'>";
        echo "<td width='8%' style='padding:.5pt .5pt .5pt .5pt' >".text($row['visit_date'])."</td>";
        echo "<td width='62%' style='padding:.5pt .5pt .5pt 12pt'> ".text($row['reason'])."</td>";
        echo "<td width='15%' style='padding:.5pt .5pt .5pt .5pt'>".text($row['provider_name'])."</td>";
        echo "<td width='12%' style='padding:.5pt .5pt .5pt .5pt'>".text($row['facility'])."</td>";
        echo "<td width='3%' style='padding:.5pt .5pt .5pt .5pt'>".text($row[''])."</td>";
        echo "</tr>";
      }
      else {
        echo "<tr class='".text($class)."'>";
        echo "<td style='padding:.75pt .75pt .75pt .75pt;border-top:solid black 1.0pt' >".text($row['visit_date'])."</td>";
        echo "<td style='padding:.75pt .75pt .75pt .75pt;border-top:solid black 1.0pt'>".text($row['reason'])."</td>";
        echo "<td style='padding:.75pt .75pt .75pt .75pt;border-top:solid black 1.0pt'>".text($row['provider_name'])."</td>";
        echo "<td style='padding:.75pt .75pt .75pt .75pt;border-top:solid black 1.0pt'>".text($row['facility'])."</td>";
        echo "<td style='padding:.75pt .75pt .75pt .75pt;border-top:solid black 1.0pt'>".text($row['encounter'])."</td>";
        echo "</tr>";
      }
    }

    echo "</table>";
} else {
    echo xlt("No Results");
}
?>
