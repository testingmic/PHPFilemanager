<?php
$PAGETITLE = "Upload Files";
REQUIRE "TemplateHeader.php";
GLOBAL $directory, $session;
$FILE_FOUND = FALSE;
// initialize some sessions
$session->unset_userdata('replaceItemId');
if(!$session->userdata(ROOT_FOLDER)) {
	$session->set_userdata(ROOT_FOLDER, 0);
}
// check the url that has been parsed
if(confirm_url_id(1, 'Replace')) {
	if(confirm_url_id(2)) {		
		if(confirm_url_id(3)) {
			$item_id = base64_decode($SITEURL[2]);
			$item_slug = xss_clean($SITEURL[3]);
			$shared_Files = $DB->where(
				'_shared_listing', '*', 
				ARRAY(
					'shared_slug'=>"='$item_slug'",
					'shared_with'=>"LIKE '%/".$session->userdata(UID_SESS_ID)."/%'",
					'shared_item_id'=>"='$item_id'",
					'shared_status'=>"='1'",
					'shared_expiry'=>" > ".time(),
			));
			IF($DB->num_rows($shared_Files) == 1) {
				# ASSIGN A TRUE VALUE TO THE USER FOUND 
				$FILE_FOUND = TRUE;
				$PAGETITLE = $directory->item_by_id2('item_title', $item_id);
				$session->set_userdata('replaceItemId', $item_id);
				$session->unset_userdata('sharedItemId');
			}			
		}
	}	
}
?>
<!--main-container-part-->
<div id="content">
<!--breadcrumbs-->
  <div id="content-header">
    <div id="breadcrumb"> <a href="<?php print $config->base_url(); ?>Dashboard" title="Go to Home" class="tip-bottom"><i class="icon-home"></i> Home</a>  <i class="icon-download"></i> File Upload</div>
  </div>
<!--End-breadcrumbs-->
<!--Action boxes-->
<div class="container-fluid">
<!--End-Action boxes--> 
<!--Chart-box-->    
<div class="row-fluid">
  <div class="widget-box">
	<div class="widget-title bg_lg"><span class="icon"><i class="icon-signal"></i></span>
	  <h5>Upload Files</h5>
	</div>
	<div class="widget-content">
		<?php print $notices->get_notification('disk_full')->result; ?>
		<?php print $notices->get_notification('daily_usage')->result; ?>
	</div>
	<div class="widget-content" >
	  <?php if( $FILE_FOUND ) { ?>
		<div class="">
		<input type="hidden" readonly id="replace_file" name="replace_file" value="success" content="<?php print $session->userdata("sharedItemUrl"); ?>">
		<?php foreach($shared_Files as $Files) { ?>
		<span class='alert alert-primary' style="width:100%">You are about to replace the file <strong><?php print $PAGETITLE; ?></strong> which was uploaded by <strong><?php print $admin_user->get_details_by_id($Files["shared_by"])->funame; ?></strong> on <strong><?php print $directory->item_by_id2('date_added', $item_id); ?></strong></span>
		<?php } ?>
		</div>
		<br clear="both">
	  <?php } ELSE { ?>
	  <input type="hidden" readonly id="replace_file" name="replace_file" value="undefined" content="">
	  <?PHP } ?>
	  
		<div class="">
		<span class='alert alert-primary' style="width:100%">The files will be uploaded into the  <strong class="get_current_upload_folder"><?php if( $session->userdata(ROOT_FOLDER) ) { ?><a href='<?php print $config->base_url(); ?>ItemStream/Id/<?php print $directory->item_by_id('item_unique_id', $session->userdata(ROOT_FOLDER)); ?>'><?php print strtoupper($directory->item_by_id('item_title', $session->userdata(ROOT_FOLDER))); ?></a><?php } else { ?><a href='<?php print $config->base_url() ; ?>ItemsStream'>ROOT</a><?php } ?></strong> Folder. <a class='btn btn-primary' id='changeFolder' href="#">Change Folder?</a></span>
		<span class="reload_folders" style="margin-top:5px;">
			<select style="height:40px;padding-top:5px;width:350px" class="form-control" id="current_folder" name="current_folder" onchange="update_upload_folder(this.value)">
				<option value="0">Root Folder</option>
				<?php $directory->display_folders(0, 1, $session->userdata(ROOT_FOLDER)); ?>
			</select>
		</span>
		</div>
		<br clear="both">
	  <div class="row-fluid">
		
		<div class="span6">
			<?php if($admin_user->get_details_by_id($session->userdata(UID_SESS_ID))->upload_status) { ?>
			<div id="drag-and-drop-zone" class="dm-uploader p-5">
				<h3 class="mb-5 mt-5 text-muted">Drag &amp; drop files here</h3>
				<div class="btn btn-primary btn-block mb-5">
					<span>Open the file Browser</span>
					<input type="file" title='Click to add Files' />
				</div>
			</div>
			<?php }  else { ?>
			<div class="dm-uploader p-5">
				<h4 class="mb-5 mt-5 text-muted alert alert-danger">Sorry! You are not permitted to upload files unto this server. Please contact the Administrator if problem persists.</h4>
			</div>
			<?php } ?>
		</div>
		<div class="span6">
		  <div class="card h-100">
            <div class="card-header">
              File List
            </div>
            <ul class="list-unstyled p-2 d-flex flex-column col" id="files" style="min-height:250px;max-height:250px;overflow:scroll;">
              <li class="text-muted text-center empty">No files uploaded.</li>
            </ul>
          </div>
		</div>
	  </div>
	  <?php if($admin_user->get_details_by_id($session->userdata(UID_SESS_ID))->upload_status) { ?>
	  <div class="row-fluid">
        <div class="span12">
           <div class="card h-100">
            <div class="card-header">
              Debug Messages
            </div>
            <ul class="list-group list-group-flush" id="debug">
              <li class="list-group-item text-muted empty">Loading plugin....</li>
            </ul>
          </div>
        </div>
      </div> <!-- /debug -->
	  <?php } ?>
	</div>
  </div>
</div>
</div>
<!--End-Chart-box-->
<?php 
REQUIRE "TemplateFooter.php";
?>