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

    $sql = "SELECT date_format(docdate,'%m/%d/%Y') docdate, c.name, d.id, d.mimetype, d.url, d.id ".
    "FROM documents AS d, categories_to_documents AS cd, categories AS c  ".
    "WHERE d.foreign_id=? and  cd.document_id = d.id AND c.id = cd.category_id ".
    "ORDER BY d.id desc";

    $res = sqlStatement($sql, array($pid));

if (sqlNumRows($res)>0) {
    ?>
    <table class="table table-striped">
        <tr>
        <th><?php echo xlt('Date'); ?></th>
        <th><?php echo xlt('Document Name'); ?></th>
        <th><?php echo xlt('Type'); ?></th>
        <th><?php echo xlt('ID'); ?></th>
        </tr>
    <?php
    $even=false;
    while ($row = sqlFetchArray($res)) {
        echo "<tr class='".text($class)."'>";
        echo "<td style='padding:.75pt .75pt .75pt .75pt'>".text($row['docdate'])."</td>";
        echo "<td style='padding:.75pt .75pt .75pt .75pt'>".text($row['name'])."</td>";
        echo "<td style='padding:.75pt .75pt .75pt .75pt'>".text($row['mimetype'])."</td>";
        echo "<td style='padding:.75pt .75pt .75pt .75pt'>".text($row['id'])."</td>";
        echo "</tr>";
    }

    echo "</table>";
} else {
    echo xlt("No Results");
}
?>
