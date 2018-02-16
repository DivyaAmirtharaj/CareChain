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

 $query = "SELECT blockchain_status, blockchain_publickey, blockchain_cloud, blockchain_cloud_username FROM patient_access_onsite WHERE pid = ? AND blockchain_publickey is not null";
 $res = sqlStatement($query, array ($pid));
 $row = sqlFetchArray($res);
 
 echo '<table border="0" id="appttable" style="width:100%;background:#eee;" class="table table-striped">';
 echo '<tr><td width="20%">Enable Blockchain:</td><td><input type="checkbox" name="enableBlockchain" onclick="return false;" ';
 echo $row['blockchain_status']==0 ? '' : 'checked' . '></td></tr>'; 
 echo '<tr><td>Public Key: </td><td> <input type="text" name="blockchain_publickey" value='.$row['blockchain_publickey']. ' disabled="true"</td></tr>';
 echo '<tr><td>Cloud Storage: </td><td> <input type="text" name="blockchain_cloud" value='.$row['blockchain_cloud']. ' disabled="true" </td></tr>';
 echo '<tr><td>Cloud Username: </td><td><input type="text" name="blockchain_cloud_username" value="'.$row['blockchain_cloud_username']. '" disabled="true"</td></tr>';
 echo '</table>';
?>
 <div style="margin: 5px 0 5px">
    <a href='#' onclick="editBlockchain('add',<?php echo attr($pid); ?>)"><button
            class='btn btn-primary'><?php echo xlt('Update'); ?></button></a>
</div>
<script>
    function editBlockchain(mode,deid){
        if(mode == 'add'){
            var title = '<?php echo xla('Update Blockchain Setting'); ?>';
            var mdata = {pid:deid};
        }
        else{
            var title = '<?php echo xla('Add Blockchain'); ?>';
            var mdata = {eid:deid};
        }
        var params = {
            buttons: [
               { text: '<?php echo xla('Cancel'); ?>', close: true, style: 'default' },
               //{ text: 'Print', close: false, style: 'success', click: showCustom }
            ],
            title: title,
            url: './update_blockchain.php',
            data: mdata
        };
        return eModal
            .ajax(params)
            .then(function () {  });
    };
</script>