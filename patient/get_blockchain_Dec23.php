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

 $query_isBlockChain = "SELECT blockchain_status FROM patient_access_onsite WHERE pid = ? AND blockchain_status!=0 ";
 $res_isBlockChain = sqlStatement($query_isBlockChain, array ( $pid ));


 $query = "SELECT blockchain_publickey FROM patient_access_onsite WHERE pid = ? AND blockchain_publickey is not null";
 $res = sqlStatement($query, array ($pid));
 $row = sqlFetchArray($res);
 
 $isBlockChain = sqlNumRows($res_isBlockChain);
 echo '<table id="appttable" style="width:100%;background:#eee;" class="table table-striped fixedtable"><thead></thead><tbody>';
 echo '<tr><td>Enable Blockchain  <input type="checkbox" name="enableBlockchain" onclick="return false;"';
 echo $isBlockChain==0 ? '' : 'checked' . '></td></tr>';
 echo '<tr><td>Public Key: <input type="text" name="blockchain_publickey" value='.$row['blockchain_publickey']. ' disabled="true" size="50" height="3" rows="4" maxlength="300"</td></tr>';
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
