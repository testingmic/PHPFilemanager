<?php
global $DB, $session, $admin_user;
header('Content-type:application/json;charset=utf-8');
$notices = load_class('notifications', 'models');
$user_agent = load_class('user_agent', 'libraries');
load_helpers('upload_helper');

TRY {
    IF (
        !ISSET($_FILES['file']['error']) ||
        IS_ARRAY($_FILES['file']['error'])
    ) {
        THROW NEW RuntimeException('Invalid parameters.');
    }

    SWITCH ($_FILES['file']['error']) {
        CASE UPLOAD_ERR_OK:
            BREAK;
        CASE UPLOAD_ERR_NO_FILE:
            THROW NEW RuntimeException('No file sent.');
        CASE UPLOAD_ERR_INI_SIZE:
        CASE UPLOAD_ERR_FORM_SIZE:
            THROW NEW RuntimeException('Exceeded filesize limit.');
        default:
            THROW NEW RuntimeException('Unknown errors.');
    }
	
	IF(!$notices->get_notification('disk_full')->can_continue) {
		THROW NEW RuntimeException('Sorry! You have reached your maximum disk space capacity. You must delete some of your files to be able to continue.');
	}
	
	IF(!$notices->get_notification('daily_usage')->can_continue) {
		THROW NEW RuntimeException('Sorry! You have reached your maximum file uploads for today.');
	}
	
	// generate a random string 
	$n_FileTitle = $_FILES['file']['name'];
	$n_FileTitle_Real = PREG_REPLACE('/\\.[^.\\s]{3,4}$/', '', $n_FileTitle);
	$n_FileName = random_string('alnum', MT_RAND(10, 30));
	$n_Thumb = random_string('alnum', MT_RAND(45, 70));
	$n_Download_Link = random_string('alnum', MT_RAND(25, 40));
	$n_FileExt = STRTOLOWER(PATHINFO($n_FileTitle, PATHINFO_EXTENSION));
	$n_FileInfo = get_file_mime($n_FileExt, 1);
	// set the item type
	$itemType = 'FILE';
	
	// set the upload file path
    $filepath = SPRINTF(config_item('upload_path').'%s', $n_FileName);
	
	$n_ThumbNail = $directory->get_thumbnail_by_ext($n_FileExt);
   	
	// confirm the the replaceItemId session has been set
	IF($session->userdata("replaceItemId")) {
		// set a variable for the item_id
		$item_id = $session->userdata("replaceItemId");
		// get the file name for the item to be replaced
		$FileName = $directory->item_by_id2('item_title', $item_id);
		$FileSlug = $directory->item_by_id2('item_unique_id', $item_id);
		$FileExt = $directory->item_by_id2('item_ext', $item_id);
		
		// ensure the files have the same type and extension 
		IF($FileExt == $n_FileExt) {
			// upload the file
			IF (!move_uploaded_file( $_FILES['file']['tmp_name'], $filepath )) {
				THROW NEW RuntimeException('Failed to move uploaded file.');
			}
			
			// get the file size
			$n_FileSize = file_size_convert(config_item('upload_path')."$n_FileName");
			$n_FileSize_KB = file_size(config_item('upload_path')."$n_FileName");
			$n_OldName = "old_".random_string('alnum', MT_RAND(45, 70));
			
			// rename the old file with an appended unique name
			@rename(config_item('upload_path')."$FileSlug", config_item('upload_path')."$n_OldName");
			
			// rename the new file to take the information of the new file
			@rename($filepath, config_item('upload_path')."$FileSlug");
			
			// update the database with the new information 
			$DB->query("UPDATE _item_listing SET 
				user_id='{$session->userdata(UID_SESS_ID)}',
				item_size='$n_FileSize', item_size_kilobyte='$n_FileSize_KB',
				WHERE id='$item_id'
			");
			
			// insert item history into the database
			$DB->query("INSERT INTO _item_listing_history SET
				item_title='$n_OldName', item_id='$item_id',
				replaced_by='{$session->userdata(UID_SESS_ID)}'
			");
			
			// alert all users that the file has been replaced
			$ip = $user_agent->ip_address();
			$br = $user_agent->browser()." ".$user_agent->platform();
			
			$DB->query("INSERT INTO _shared_comments SET file_id='$item_id', user_id='{$session->userdata(UID_SESS_ID)}', comment='The file has been replaced with a newer version. Please do well to check it out by clicking on the download link.', user_agent='$ip: $br', class='warning'");
		
			// unset the session for the item to be replaced
			$session->unset_userdata("replaceItemId");
		} ELSE {
			ECHO json_encode([
				'status' => 'error',
				'message' => 'Sorry! The files should be of the same type. (The files must have the same extension.)'
			]);
		}
	} ELSE {
		
		// upload the file
		if (!move_uploaded_file( $_FILES['file']['tmp_name'], $filepath )) {
			THROW NEW RuntimeException('Failed to move uploaded file.');
		}
		
		// get the file size
		$n_FileSize = file_size_convert(config_item('upload_path')."$n_FileName");
		$n_FileSize_KB = file_size(config_item('upload_path')."$n_FileName");
		
		IF(IN_ARRAY($n_FileExt, ARRAY("jpg", "png", "gif", "jpeg","bmp","jpg2"))) {
			create_thumbnail(config_item('upload_path').$n_FileName, config_item('thumbnail_path')."$n_Thumb.".$n_FileExt);
			$n_ThumbNail = config_item('thumbnail_path').$n_Thumb.".$n_FileExt";
		} 
	
		// insert the new record into the database item listing table 
		$DB->query("INSERT INTO _item_listing SET 
			user_id='{$session->userdata(UID_SESS_ID)}',
			office_id='{$session->userdata(OFF_SESSION_ID)}',
			item_users='{$admin_user->return_username()}', 
			item_title='$n_FileTitle_Real', item_unique_id='$n_FileName',
			item_type='$itemType', item_ext='$n_FileExt',
			item_download_link='$n_Download_Link',
			file_type='$n_FileInfo',
			item_date=now(), item_thumbnail='$n_ThumbNail',
			item_parent_id='{$session->userdata(ROOT_FOLDER)}', 
			item_folder_id='{$session->userdata(ROOT_FOLDER)}',
			item_size='$n_FileSize', item_size_kilobyte='$n_FileSize_KB'
		");
	}
		
    // All good, send the response
    ECHO json_encode([
        'status' => 'ok',
        'path' => $filepath,
		'message'=> "<a href='{$config->base_url()}ItemStream/Id/$n_FileName'>$n_FileTitle_Real.$n_FileExt</a> - File upload was successful"
    ]);

} CATCH (RuntimeException $e) {
	// Something went wrong, send the err message as JSON
	http_response_code(400);

	ECHO json_encode([
		'status' => 'error',
		'message' => $e->getMessage()
	]);
}
