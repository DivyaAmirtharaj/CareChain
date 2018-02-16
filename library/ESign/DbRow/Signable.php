<?php

namespace	ESign;


require_once $GLOBALS['srcdir'].'/ESign/SignableIF.php';
require_once $GLOBALS['srcdir'].'/ESign/Signature.php';
require_once $GLOBALS['srcdir'].'/ESign/Utils/Verification.php';

abstract class DbRow_Signable	implements SignableIF
{
	private	$_signatures = array();
	private	$_tableId	=	null;
	private	$_tableName	=	null;
	private	$_verification = null;

	public function	__construct($tableId,	$tableName)
	{
			$this->_tableId	=	$tableId;
			$this->_tableName	=	$tableName;
			$this->_verification = new Utils_Verification();
	}
	
	public function	getSignatures()
	{
			$this->_signatures = array();
			
			$statement = "SELECT E.id, E.tid,	E.table, E.uid,	U.fname, U.lname,	E.datetime,	E.is_lock, E.amendment,	E.hash,	E.signature_hash FROM	esign_signatures E ";
			$statement .=	"JOIN	users	U	ON E.uid = U.id	";
			$statement .=	"WHERE E.tid = ? AND E.table = ? ";
			$statement .=	"ORDER BY	E.datetime ASC";
			$result	=	sqlStatement($statement, array(	$this->_tableId, $this->_tableName ));
			
			while	($row	=	sqlFetchArray($result))	{
					$signature = new Signature(
							$row['id'],
							$row['tid'],
							$row['table'],
							$row['is_lock'],
							$row['uid'],
							$row['fname'],
							$row['lname'],
							$row['datetime'],
							$row['hash'],
							$row['amendment'],
							$row['signature_hash']
					);
					$this->_signatures[]=	$signature;
			}
			
			return $this->_signatures;
	}
	
	/**
	 * Get the hash	of the last	signature	of type	LOCK.
	 *
	 * This	is used	for	comparison with	a	current	hash to
	 * verify	data integrity.
	 *
	 * @return sha1|empty	string
	 */
	protected	function getLastLockHash()
	{
			$statement = "SELECT E.tid,	E.table, E.hash	FROM esign_signatures	E	";
			$statement .=	"WHERE E.tid = ? AND E.table = ? AND E.is_lock = ? ";
			$statement .=	"ORDER BY	E.datetime DESC	LIMIT	1";
			$row = sqlQuery($statement,	array( $this->_tableId,	$this->_tableName, SignatureIF::ESIGN_LOCK ));
			$hash	=	null;
			if ($row &&	isset($row['hash'])) {
					$hash	=	$row['hash'];
			}

			return $hash;
	}

	public function	getTableId()
	{
			return $this->_tableId;
	}
	
	public function	renderForm()
	{
			include	'views/esign_signature_log.php';
	}
	
	public function	isLocked()
	{
			$statement = "SELECT E.is_lock FROM	esign_signatures E ";
			$statement .=	"WHERE E.tid = ? AND E.table = ? AND is_lock = ? ";
			$statement .=	"ORDER BY	E.datetime DESC	LIMIT	1	";
			$row = sqlQuery($statement,	array( $this->_tableId,	$this->_tableName, SignatureIF::ESIGN_LOCK ));
			if ($row &&	$row['is_lock']	== SignatureIF::ESIGN_LOCK)	{
					return true;
			}
			
			return false;
	}

	public function	sign($userId, $lock	= false, $amendment	= null)
	{
			$encounter_id = $this->_tableId;
			
			
			//Get	Patient	Data
			$pat_sql = "select concat(p.fname,' ',p.lname) as patient_name, concat(p.fname,p.lname) as patient_fullname, date_format(dob,'%m-%d-%Y') as	patient_dob,concat(u.fname,' ',u.lname)	AS provider_name,	";
			$pat_sql.= "e.facility,	date_format(e.date,'%m-%d-%Y') as encounter_date, p.pid as pid, current_timestamp as timestamp, e.facility_id from form_encounter e, users u, patient_data p	";
			$pat_sql.= "where e.encounter=".$encounter_id." and e.provider_id = u.id and p.pid=e.pid";

			$pat_res = sqlQuery($pat_sql);
			
			$pid=$pat_res['pid'];
			$facility_id=$pat_res['facility_id'];

			//Get	Current	Blockchain status	for	the	patient
			$curr_block_sql	= "select curr_block_hash_key, next_block_number, blockchain_cloud from patient_access_onsite where pid=".$pid;
			$curr_block_res	= sqlQuery($curr_block_sql);

			$patient_dir='../../../../../CloudStorage/CareChain_Patients/'.$pat_res['patient_fullname'];
			$facility_dir='../../../../../CloudStorage/CareChain_Facilities/'.str_replace(' ', '', $pat_res['facility']);
			$carechain_dir='../../../../../CloudStorage/CareChain';
		    			
			if (!is_dir($patient_dir.'/pendants')) {
				mkdir($patient_dir.'/pendants', 0777, true);
			}

			if (!is_dir($facility_dir.'/pendants')) {
				mkdir($facility_dir.'/pendants', 0777, true);
			}

			if (!is_dir($carechain_dir)) {
				mkdir($carechain_dir, 0777, true);
			}


			//Get Encounter Data
			$visit_res = sqlStatement("select sort_ord as	Line_number, reason	as Encounter from visits where encounter=? order by	sort_ord", $this->_tableId);
			$visit_rows	= array();
			while ($rows = sqlFetchArray($visit_res)) {
				$visit_rows[] = $rows;
			}
			$pid=$pat_res['pid'];
			
			$json_care_data = (object)	[
				'type' => "Medical",
				'provider_name'	=> $pat_res['provider_name'],
				'date' => $pat_res['encounter_date'],
				'data_type' => "text",
				'data' => $visit_rows];
				
			$care_data = json_encode($json_care_data,	JSON_PRETTY_PRINT);

			//Get Current	Blockchain status for	the	patient
			$curr_block_sql	= "select curr_block_hash_key, next_block_number from patient_access_onsite	where pid=".$pid;
			$curr_block_res	= sqlQuery($curr_block_sql);
			$next_block_number = $curr_block_res['next_block_number'];
			if ($next_block_number < 1){ 
				
				//Genesis	Block.	Just insert	Patient	details	on it
				$json_gdata	=	(object) ['block_number' => 0,
				'patient_id' => $pid,
				'patient_name' =>	$pat_res['patient_name'],
				'patient_dob' =>	$pat_res['patient_dob'],
				'block_timestamp' => $pat_res['timestamp'],
				'note'	=> "Genesis Block"];

				$gen_block_data = json_encode($json_gdata,	JSON_PRETTY_PRINT);
				$gen_block_size = strlen($gen_block_data);
				$gen_block_sign_key = hash('sha256', $gen_block_data);

//				$gen_block_ins_sql = "INSERT INTO blockchain(block_hash_key, pid, block_size, block_number, block_data)	".
//				"values('".$gen_block_sign_key."',".$pid.",". $gen_block_size.",0,'".$gen_block_data."')";
//				sqlStatement($gen_block_ins_sql);
				$next_block_number = $next_block_number	+ 1; 

				$filename='/'.$gen_block_sign_key.'.json';
				$fp	= fopen($patient_dir.$filename, 'w');
				fwrite($fp,$gen_block_data);
				fclose($fp); 
				
				copy($patient_dir.$filename, $facility_dir.$filename);
				copy($patient_dir.$filename, $carechain_dir.$filename);
				//Created	Genesis	block	and	assign the Genesis block key as	the	prev key for the actual	block
				$prev_block_hash_key = $gen_block_sign_key;	 
			 }
			 else{
				$prev_block_hash_key=$curr_block_res['curr_block_hash_key'];
			 }
			 

			if ($lock){
				$care_data_key = hash('sha256', $care_data);
			   // For Patient

				$encrypt_block_sql = "select AES_ENCRYPT('".$care_data."', blockchain_publickey) as encrypt_data from patient_access_onsite where pid=".$pid;
				$encrypt_block_res = sqlQuery($encrypt_block_sql);
				$care_encrypt_data = $encrypt_block_res['encrypt_data'];
	
	
				$filename = $patient_dir.'/pendants/'.$care_data_key.'.json';
				
				$fp = fopen($filename, 'w');
				fwrite($fp,$care_encrypt_data);
				fclose($fp); 
				
			   // For Facility
				$encrypt_block_sql = "select AES_ENCRYPT('".$care_data."', blockchain_publickey) as encrypt_data from facility where id=".$facility_id;
				$encrypt_block_res = sqlQuery($encrypt_block_sql);
				$care_encrypt_data = $encrypt_block_res['encrypt_data'];
				
				$filename = $facility_dir.'/pendants/'.$care_data_key.'.json';
				$fp = fopen($filename, 'w');
				fwrite($fp,$care_encrypt_data);
				fclose($fp); 
			   $block_care_data = $care_data_key;
			}
			else{
				$block_care_data = $json_care_data;
			}

			
			$json_data = (object) [
				'prev_block_hask_key'=> $prev_block_hash_key,
				'block_number' => $next_block_number,
				'block_timestamp' => $pat_res['timestamp'],
				'patient_name' => $pat_res['patient_name'],
				'patient_dob'=> $pat_res['patient_dob'],
				'care_data' => $block_care_data];
			
			$block_data = json_encode($json_data,	JSON_PRETTY_PRINT);
			$block_size = strlen($block_data);
			$block_sign_key = hash('sha256', $block_data);
			
			
//			$insert_block_sql = "INSERT INTO zblockchain(encounter_id,block_data) values(".$encounter_id.",'".$block_data."') ON DUPLICATE KEY UPDATE block_data='".$block_data."'";
//			sqlStatement($insert_block_sql);
		
			// Updating	Next Hash	Key	on the last	block	for	the	patient	to the latest	data block key
//			$updt_curr_block_sql = "UPDATE blockchain	set	next_block_hash_key='".$block_sign_key."' where next_block_hash_key is null and pid=".$pid;
//			sqlStatement($updt_curr_block_sql);

//			$block_ins_sql = "INSERT INTO blockchain(block_hash_key, pid, prev_block_hash_key, block_size, block_number, block_data) ".
//			"values('".$block_sign_key."',".$pid.",'".$prev_block_hash_key."',".$block_size.",".$next_block_number.",'".$block_data."')	".
//			"ON	DUPLICATE	KEY	UPDATE block_data='".$block_data."', block_hash_key='".$block_sign_key."'";
//			sqlStatement($block_ins_sql);
				
			$next_block_number = $next_block_number + 1;

			$updt_curr_stat_block_sql = "UPDATE	patient_access_onsite set curr_block_hash_key='".$block_sign_key."', next_block_number=".$next_block_number."	where	pid	=	".$pid;
			sqlStatement($updt_curr_stat_block_sql);

			$block_sign = hash('sha256', $block_data);
			
			$filename = '/'.$block_sign.'.json';
			
			$fp	= fopen($patient_dir.$filename, 'w');
			fwrite($fp,$block_data);
			fclose($fp);
			copy($patient_dir.$filename, $carechain_dir.$filename);
			copy($patient_dir.$filename, $facility_dir.$filename);

			$statement = "INSERT INTO `esign_signatures` ( `tid`,`table`, `uid`,`datetime`,`is_lock`, `hash`, `amendment`,`signature_hash` ) ";
			$statement .= "VALUES (?, ?,?, NOW(),?, ?,?, ? ) ";
			
			// Make	type string
			$isLock	=	SignatureIF::ESIGN_NOLOCK;
			if ($lock) {
					$isLock	= SignatureIF::ESIGN_LOCK;
			}
			
			// Create a hash of the signable object so we can verify it's integrity
			$hash = $this->_verification->hash($this->getData());
			
			// Crate a hash of the signature data itself. This is the same data as Signature::getData() method
			$signature = array(
					$this->_tableId,
					$this->_tableName,
					$userId,
					$isLock,
					$hash,
					$amendment );
			$signatureHash = $this->_verification->hash($signature);
			
			// Append	the	hash of	the	signature	data to	the	insert array before	we insert
			$signature[]=	$signatureHash;
			$id	=	sqlInsert($statement,	$signature);
			
			if ($id	===	false) {
					throw	new	\Exception("Error occured while attempting to insert a signature into the database.");
			}
			
			return $id;
	}
	
	public function	verify()
	{
			$valid = true;
			// Verify	the	signable data	integrity
			// Check to	see	if this	SignableIF is	locked
			if ($this->isLocked()) {
					$signatures	=	$this->getSignatures();
			
					// SignableIF	is locked, so	if it	has	any	signatures,	make sure	it hasn't	been edited	since	lock
					if (count($signatures))	{
							// Verify	the	data of	the	SignableIF object
							$lastLockHash	=	$this->getLastLockHash();
							$valid = $this->_verification->verify($this->getData(),	$lastLockHash);
							
							if ($valid === true) {
									// If	still	vlaid, verify	each signatures' integrity
									foreach	($signatures as	$signature)	{
											if ($signature instanceof	SignatureIF) {
													$valid = $signature->verify();
													if ($valid === false)	{
															break;
													}
											}
									}
							}
					}
			}
			
			return $valid;
	}
}
