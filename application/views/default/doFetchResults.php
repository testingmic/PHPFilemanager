<?php
#call the GLOBAL function 
GLOBAL $SITEURL, $config, $DB, $admin_user, $session, $offices;
# confirm that the user is logged in 
IF($admin_user->logged_InControlled()) {
	#confirm that the user has parsed this value
	IF(ISSET($SITEURL[1])) {
		# THIS SECTION CALLS THE LIST OF DOCTORS OR HEALTH PRACTITIONERS IN 
		# THE DATABASE THIS SECTION WILL ALSO EMPLOY THE USE OF SEARCH PATTERNS
		IF(($SITEURL[1] == "doListing") AND ISSET($_POST["_c_row"])) {
			
			# SET UP THE QUERY RESULTS STRINGS
			$queryResult = "<div style='margin-top:10px; font-size:14px; width:100%' class='text-center alert alert-primary'>Query results for Uploaded Files & Folders: ";
			
			# ASSIGN VARIABLES TO THE INFORMATION
			$queryString = " ";
			$_rows_per_page = config_item('rowsperpage');
			$_listing = load_class('directories', 'models');
			
			# GET THE INFORMATION THAT WAS PARSED BY THE USER
			IF(ISSET($_POST["_c_row"])) {			
				$_c_row = (INT)xss_clean($_POST["_c_row"]);
			} ELSE {
				$_c_row = 0;
			}
			
			$query = $_listing->list_all_files(NULL, "AND item_type='FILE' AND item_parent_id='0'");
			$query_Folders = $_listing->list_all_files(NULL, "AND item_type='FOLDER' AND item_parent_id='0'");
			$query_next = $_listing->list_all_files("$queryString LIMIT $_c_row, $_rows_per_page", "AND item_type='FILE' AND item_parent_id='0'");
			
			
			$queryResult .= " | <strong class='rTRowCount'>" . $DB->num_rows($query_next) . "</strong> results found";
			
			
			$queryResult .= "</div><br clear='both'>";
			
			IF($DB->num_rows($query_next) > 0) {
				IF(!ISSET($_POST["load-more"])) {
					PRINT $queryResult;
				}
			}
			
			IF(!ISSET($_POST["load-more"])) {
				FOREACH($query_Folders AS $Folders) {
					
					$file_ext = $Folders["item_ext"];
					$fileName = $Folders["item_title"];
					$Id = $Folders["id"];
					$Uid = $Folders["item_unique_id"];
					
					PRINT "<div class='file File_Info_$Id' onmouseout='hide_item(\"$Id\")' onmouseover='show_item(\"$Id\")'><a href='".$config->base_url()."ItemStream/Id/$Uid'><img src='".$config->base_url().$Folders['item_thumbnail']."'><br>$fileName</a> <br>
					<div class='file_option' id='option_$Id'>
						<span onclick='process_item(\"delete\", \"$Id\", \"FOLDER\", \"".$session->userdata(UID_SESS_ID)."\");' class='btn btn-danger'><i class='icon-trash'></i> Delete Folder</span>
					</div>
					</div>";
				}
			}
			
			IF($DB->num_rows($query_next) > 0) {
				
				FOREACH($query_next AS $Files) {
					
					$file_ext = $Files["item_ext"];
					$fileName = $Files["item_title"];
					$Id = $Files["id"];
					$Uid = $Files["item_unique_id"];
					$DLink = $Files["item_download_link"];
					
					PRINT "<div class='file File_Info_$Id' onmouseout='hide_item(\"$Id\")' onmouseover='show_item(\"$Id\")'>";
					
					PRINT "<a title='Click to view full details of this file.' href='".$config->base_url()."ItemStream/Id/$Uid'><img src='".$config->base_url().$Files['item_thumbnail']."'><br>$fileName</a><br>";
					
					PRINT "<div class='file_option' id='option_$Id'>";
					
					// CONFIRM THAT THE FILE IS A ZIP FILE
					IF($file_ext == "zip") {
						PRINT "<span value='$Id' title='Extract File' data-toggle=\"modal\" data-target=\"#extractZippedItem\" class='btn btn-primary extract_zip'><i class='icon-bookmark'></i></span> ";
					} ELSE {
						// CHECK IF THE FILE IS PART OF THE THE LIST OF EDITABLE FILES
						IF(IN_ARRAY(".".$file_ext, config_item("editable_ext"))) {
							PRINT "<span title='Edit the contents of this file.' class='btn btn-primary'><a style='color:#fff' href='".$config->base_url()."ItemStream/Id/$Uid/Edit'><i class='icon-edit'></i></a></span> ";
						} ELSE {							
							PRINT "<span title='Click to view the full contents of this file.' onclick='process_item(\"edit\", \"$Uid\", \"FILE\", \"".$session->userdata(UID_SESS_ID)."\");' class='btn btn-primary'><i class='icon-eye-open'></i></span> ";
						}
					}
					PRINT "<span title='Download this file' class='btn btn-success'><a style='color:#fff' href='".$config->base_url()."Download/$DLink' target='_blank'><i class='icon-download'></i></a></span> ";
					PRINT "<span title='Add File to Share List' onclick='add_share_item(\"$Uid\",\"$fileName\");' class='btn btn-warning'><i class='icon-plus'></i></span> ";
					PRINT "<span title='Click to delete this file.' onclick='process_item(\"delete\", \"$Id\", \"FILE\", \"".$session->userdata(UID_SESS_ID)."\");' class='btn btn-danger'><i class='icon-trash'></i></span>";
					PRINT "</div></div>";			
				}
				?>
				<script>
					var c_row = Number($("#_c_row").val())+<?php print $_rows_per_page; ?>;
					$("#_c_row").val(c_row);
					<?PHP IF($_c_row == 0) { ?>
					$(".rTRowCount").text(<?php print $DB->num_rows($query); ?>);
					<?PHP } ELSE { ?>
					$(".rTRowCount").text(c_row);
					<?PHP } ?>
					$("#_allcount").val(<?php print $DB->num_rows($query); ?>);
				</script>
				<?php } ELSE { ?>
				<?php IF(!ISSET($_POST["load-more"])) { ?>
				<?PHP PRINT $queryResult; ?>
				<div style='margin-top:10px;' class='text-center alert btn-danger'>Sorry! There was no results found found for your search filter.</div>
				<script>
					$(".load-more").hide();
				</script>
				<?php } ?>
			<?php }
		}
		
		# THIS SECTION SEARCHS FOR A CATEGORY BASED ON THE USER
		ELSEIF(($SITEURL[1] == "ListCategories") AND ISSET($_POST["ListCategories"]) AND ISSET($_POST["specialty"]) ) {	
			# START PROCESSING THE FORM
			$specialty = xss_clean($_POST["specialty"]);
			
			$sql = $DB->query("SELECT * FROM ".SPECIALTY_CATEGORY." WHERE `name` LIKE '%$specialty%' LIMIT 15");
			IF($DB->num_rows($sql) > 0){
				PRINT "<ul>";
				FOREACH($sql AS $row){
					print '<li><a style="cursor:pointer" class="autolist" onclick="javascript:add_field(\''.UCWORDS($row['slug']).'\');">'.UCWORDS($row["name"]).'</a></li>';
				}
				PRINT "</ul>";
			}
		}
		
		# THIS SECTION SEARCHS FOR A CATEGORY BASED ON THE USER
		ELSEIF(($SITEURL[1] == "ListMembers") AND ISSET($_POST["ListMembers"]) ) {	
			# START PROCESSING THE FORM
			$members = xss_clean($_POST["members"]);
			
			$sql = $DB->query("SELECT * FROM ".PRACTITIONERS_TABLE." WHERE `fullname` LIKE '%$members%' AND user_status='1' LIMIT 15");
			IF($DB->num_rows($sql) > 0){
				PRINT "<ul>";
				FOREACH($sql AS $row){
					print '<li><a style="cursor:pointer" class="autolist" href="'.$config->base_url().'Details/'.$row['unique_id'].'">'.UCWORDS($row["fullname"]).'</a></li>';
				}
				PRINT "</ul>";
			}
		}
		
		# THIS ELSE PART WILL BE THE SUPER ERROR MESSAGE DISPLAY
		# SHOULD IN CASE THE USER MISSES OUT KEY PARAMETERS FOR VALIDATION
		ELSE {
			PRINT '<script>$(\'#more-div\').css("display","none");</script>';
			show_error('Invalid fields submitted', 'Sorry! You have wrongly submitted wrong parameters for validation. Please check and try again.', 'error_404');
		}
	} ELSE {
		PRINT '<script>$(\'#more-div\').css("display","none");</script>';
		show_error('Page Not Found', 'Sorry the page you are trying to view does not exist on this server', 'error_404');
	}
} ELSE {
	show_error('Page Not Found', 'Sorry the page you are trying to view does not exist on this server', 'error_404');
}
?>