<?php
/*
Plugin Name: Simple but powerful HTML and PDF Job Board
Plugin URI: http://askella.de/
Description: SPJB allows users to quickly create job offers as HTML page and PDF file using the WordPress WYSIWYG editor. Templates are supported to significantly speed up the recruitment process.
Version: 0.9
Author: Michael Nissen
Author URI: http://michaelnissen.de/
*/

register_activation_hook(__FILE__, 'spjb_initialize');
register_uninstall_hook(__FILE__, 'spjb_uninstall');
add_action('admin_menu', 'spjb_register_admin_menu');
add_action('admin_enqueue_scripts', 'spjb_backend_scripts');
add_action('wp_ajax_spjb_load_content', 'spjb_load_content');
add_shortcode('jobboard', 'spjb_frontend_jobboard');

function spjb_initialize(){
	global $wpdb;
	$charset = '';
	$prefix = $wpdb->prefix;
	
	/* Setting correct character set options */
	if ( ! empty( $wpdb->charset ) ) {
	  $charset = "DEFAULT CHARACTER SET {$wpdb->charset}";
	}

	if ( ! empty( $wpdb->collate ) ) {
	  $charset .= " COLLATE {$wpdb->collate}";
	}
	
	/* Creating the actual (seperate) tables */
	$sql0 = '
	CREATE TABLE IF NOT EXISTS '.$prefix.'spjb_jobs  
	(
	id int NOT NULL,
	type varchar(512),
	title varchar(512),
	content text,
	htmllink text,
	relhtmllink text,
	pdflink text,
	relpdflink text,
	applylink varchar(4096),
	boards varchar(4096),
	PRIMARY KEY (id)
	) '.$charset.';';
	
	$sql1 = 'CREATE TABLE IF NOT EXISTS '.$prefix.'spjb_text 
	(
	id int NOT NULL,
	title varchar(512),
	value text,
	PRIMARY KEY (id)
	) '.$charset.';';
	
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');
	dbDelta($sql0);
	dbDelta($sql1);
	
	/* Adding the initial WP-based options */
	
	add_option('spjb_version', '1.0');
	add_option('spjb_current_jobs', '0');
	add_option('spjb_current_templates', '0');
	add_option('spjb_currentjobid', '1');
	add_option('spjb_currenttemplateid', '1');
	add_option('spjb_types', array());
	add_option('spjb_shortcodes', array());
}

function spjb_uninstall(){
	if(!current_user_can('activate_plugins')) return; /*|| __FILE__ != WP_UNINSTALL_PLUGIN) return; */
	if(!defined('WP_UNINSTALL_PLUGIN')) exit();
	
	global $wpdb;
	$prefix = $wpdb->prefix;
	$wpdb->query("DROP TABLE IF EXISTS ".$prefix."spjb_jobs");
	$wpdb->query("DROP TABLE IF EXISTS ".$prefix."spjb_text");
	
	delete_option('spjb_version');
	delete_option('spjb_current_jobs');
	delete_option('spjb_current_templates');
	delete_option('spjb_currentjobid');
	delete_option('spjb_currenttemplateid');
	delete_option('spjb_types');
	delete_option('spjb_shortcodes');
}

function spjb_register_admin_menu(){
	$capability = 'manage_options';
	add_menu_page('Pretty Simple Job Board', 'Jobs', 'nosuchcapability', 'spjb-main', NULL);
	add_submenu_page('spjb-main', 'Overview / How to', 'Instructions and Overview', $capability, 'spjb-overview', 'spjb_load_admin_submenu_overview');
	add_submenu_page('spjb-main', 'Create new job', 'Create new job', $capability, 'spjb-add-job', 'spjb_load_admin_submenu_add_job');
	add_submenu_page('spjb-main', 'Manage jobs', 'Manage jobs', $capability, 'spjb-manage-jobs', 'spjb_load_admin_submenu_manage_jobs');
	add_submenu_page('spjb-main', 'Add new template', 'Create new template', $capability, 'spjb-add-template', 'spjb_load_admin_submenu_add_template');
	add_submenu_page('spjb-main', 'Manage templates', 'Manage templates', $capability, 'spjb-manage-templates', 'spjb_load_admin_submenu_manage_templates');
	add_submenu_page('spjb-main', 'General settings', 'Settings', $capability, 'spjb-settings', 'spjb_load_admin_submenu_settings');	
}

function spjb_backend_scripts(){
	if(is_admin()){
		wp_register_style('spjb_admin_css', plugins_url('includes/spjb_admin.css', __FILE__));
		wp_enqueue_style('spjb_admin_css');
		
		/*wp_deregister_script('jquery');
		wp_register_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js');*/
		wp_enqueue_script('jquery');
	}
}

function spjb_load_content(){
	if(isset($_REQUEST)){
		global $wpdb;
		$tablename = $wpdb->prefix . 'spjb_text';
		$results = $wpdb->get_row('SELECT * FROM '.$tablename.' WHERE id='.$_REQUEST['id'].'');
		echo stripslashes($results->value);
	}
	die();
}

function spjb_frontend_jobboard($atts){
	
	/* $atts-Array may contain the following elements:
	 * board -> respective job board shortcode
	 * id, type, title, linkhtml, linkpdf, applyurl
	 * 
	 * if the individual element is set, it's content will be used as the table header name */
	
	ob_start();
	
	// Set defaults (purposely not done via shortcode_atts)
	if(!isset($atts['linkhtmltext'])) $atts['linkhtmltext'] = '[HTML]';
	if(!isset($atts['linkpdftext'])) $atts['linkpdftext'] = '[PDF]';
	if(!isset($atts['applyurltext'])) $atts['applyurltext'] = '[Apply]';
	
	// If no atts but board are supplied, use defaults
	if(isset($atts['board']) && !isset($atts['id']) && !isset($atts['type']) && !isset($atts['title']) && !isset($atts['linkhtml']) && !isset($atts['linkpdf']) && !isset($atts['applyurl'])){
		$atts['id'] = "ID";
		$atts['type'] = "Type";
		$atts['title'] = "Title";
		$atts['linkhtml'] = "Link to HTML";
		$atts['linkpdf'] = "Link to PDF";
	}
	
	$board = $atts['board'];
	
	if($board != ""){
		global $wpdb;
		$tablename = $wpdb->prefix.'spjb_jobs';
		$results = $wpdb->get_results('SELECT * FROM '.$tablename.' WHERE boards LIKE "%'.$atts['board'].'%" ORDER BY id ASC');
		?>
		<table>
			
			<?php
			
			
			/* Only build the table header if any of the array elements is not empty (and therefore a header is required). Build the string in $string and echo it in the end. */
			$string = '';
			if(isset($atts['id'])) $string = $string."<th>".$atts['id']."</th>";
			if(isset($atts['type'])) $string = $string."<th>".$atts['type']."</th>";
			if(isset($atts['title'])) $string = $string."<th>".$atts['title']."</th>";
			if(isset($atts['linkhtml'])) $string = $string."<th>".$atts['linkhtml']."</th>";
			if(isset($atts['linkpdf'])) $string = $string."<th>".$atts['linkpdf']."</th>";
			if(isset($atts['applyurl'])) $string = $string."<th>".$atts['applyurl']."</th>";
			
			if(str_replace("<th>", "", str_replace("</th>", "", $string)) != ''){
				echo('<tr>'.$string.'</tr>');
			}
			
			/* End of table header. Output the actual job board. */

			foreach($results as $key => $row)
			{?>
				<tr>
					<?php
					if(isset($atts['id'])) echo("<td>".$row->id."</td>");
					if(isset($atts['type'])) echo("<td>".$row->type."</td>");
					if(isset($atts['title'])) echo("<td>".$row->title."</td>");
					if(isset($atts['linkhtml'])) echo('<td><a href="'.$row->htmllink.'">'.$atts['linkhtmltext'].'</a></td>');
					if(isset($atts['linkpdf'])) echo('<td><a href="'.$row->pdflink.'">'.$atts['linkpdftext'].'</a></td>');
					if(isset($atts['applyurl'])) echo('<td><a href="'.$row->htmllink.'">'.$atts['applyurltext'].'</a></td>');
					?>
				</tr>
			<?
			}
			?>
			
		</table><?
	}
	$table = ob_get_contents();
	ob_end_clean();
	return $table;
}

function getFooter(){
	echo '<div style="text-align:right; line-height: 24px; margin-right: 5%;">Simple but Powerful HTML and PDF Job Board created by <a href="http://askella.de"><img style="margin: 10px 2px -5px 2px;" src="http://askella.de/res/askella-wordpress-plugin-logo.png"></a>. Support and Service is available via E-Mail to <a href="mailto:service@askella.de">service@askella.de</a></div>';
}

function spjb_load_admin_submenu_overview(){?>
	<div id="spjb_admin">
	<div id="spjb_info">
		Thank you for downloading Simple but powerful HTML and PDF Job Board!<br/>
		Support and customization services (for complex *.pdf-templates) are available by E-Mail to <a href="mailto:service@askella.de">service@askella.de</a> - we're looking forward to help you!<br/>
	</div>
    <h2>Overview and instructions</h2>
	<h3>Inserting Job Boards in posts</h3>
	To display a job board in a post, please use the <strong>[jobboard board="board-identifier"]</strong> shortcode, where board-identifier reflects the setting "Shortcodes / boards" on the bottom of the Adding/editing job-submenu.<br/>
	By default (only using the board-attribute), all columns except for the Apply-to-Link will be displayed. This behaviour can be changed - read below for more information.<br/><br/>
	
	<h3>Shortcode documentation</h3>
	You can change the columns to be displayed as well as the column titles by using the attributes listed below.<br/>
	
	<ul>
		<li><strong>id</strong> - Text for ID column heading</li>
		<li><strong>type</strong> - Text for Type column heading</li>
		<li><strong>title</strong> - Text for Title column heading</li>
		<li><strong>linkhtml</strong> - Text for HTML-Link-column heading</li>
		<li><strong>linkpdf</strong> - Text for PDF-Link-column heading</li>
		<li><strong>applyurl</strong> - Text for the "Apply to" column heading</li>
		<li><strong>linkhtmltext</strong> - Anchor link text for HTML column displayed in the actual table, default: [HTML]</li>
		<li><strong>linkpdftext</strong> - Anchor link text for PDF column displayed int he actual table, default: [PDF]</li>
		<li><strong>applyurltext</strong> - Content of each table body cell of the "Apply to" column in the actual table. Can include HTML, e.g. &lt;a href="mailto:recruiting@yourdomain.com">Apply!&lt;/a></li>
	</ul>
	
	As soon as any additional attributes to board="board-identifier" are used, only the column which have been added as attribute will be displayed. If the attributes are used but left, the table-heading-row will not be displayed.<br/>
	Example: [jobboard board="us"] displays all jobs including the default table header.<br/>
	Example: [jobboard board="us" id="Job number" title="Name of position" pdflink="Download PDF"] displays all jobs, but only the columns for ID, title and pdflink (marked "Job Number, "Name of position" and "Download PDF" in the table header).<br/>
	Example: [jobboard board="us" id="" title="" pdflink="" htmllink=""] displays all jobs, but only the columns for ID, title, pdflink and htmllink without any table header.
	
	<h3>HTML Generator</h3>
	You can adjust the HTML template that is used to generate the HTML files under http://your-wp-install.com/wp-content/plugins/simple-but-powerful-html-and-pdf-job-board/includes/template.html. The default template resembles a A4-paper, often used in recruiting processes.<br/>
	The [title_placeholder] and [content_placeholder]-tags will be replaced with the actual job title and HTML content upon creation of the individual job.
	
	<h3>PDF Generator</h3>
	SPJB utilizes the TCPDF library for the generation of PDF files. Documentation can be found on <a href="http://www.tcpdf.org/">TCPDFs homepage</a>. <br/><br/>
	A file called template.pdf (in wp-content/plugins/simple-but-powerful-html-and-pdf-job-board/includes) is used for the generation of the PDF files. You may add your own template here, but please edit the file spjb_createpdf.php (found in the same folder) accordingly - otherwise your logos or other areas may be overwritten with code.<br/><br/>
	We are able to assist you with this process - please contact us via e-mail at <a href="mailto: service@askella.de">service@askella.de</a> and we will send you an offer based on your requirements.
	</div>
	<?php getFooter(); ?>
	<div style="clear: both;"></div>
<?php
}

function spjb_load_admin_submenu_add_job(){?>
	<div id="spjb_admin">
    <h2>Adding / editing job</h2>
    
    <script type="application/javascript">
    function activate(id){
	    jQuery(document).ready(function($){
	    	var data = {
	    		'action': 'spjb_load_content',
	    		'id' : id
	    	}
	    	jQuery.post(ajaxurl, data, function(response){
	    		tinymce.activeEditor.setContent(response);
	    	});
	    });
    }
    </script>
    
    <?
    /* Update the database if any options are set (if POST-variables are set) */
		
	global $wpdb;
	$tablename = $wpdb->prefix . 'spjb_jobs';
	
	$id = ""; $htmllink = ""; $pdflink = ""; $applylink = ""; $hidden;
	$type = ((isset($_POST['type']))? $_POST['type'] : ""); 
	$title = ((isset($_POST['title']))? $_POST['title'] : ""); 
	$content = ((isset($_POST['wysiwyg']))? $_POST['wysiwyg'] : ""); 
	$boards = ((isset($_POST['boards']))? $_POST['boards'] : "");
	$hidden = ((isset($_POST['hidden']))? $_POST['hidden'] : "");
	
	if(isset($_GET['id']) && isset($_GET['delete'])){
		$id = $_GET['id'];
		$wpdb->query("DELETE FROM $tablename WHERE id = $id");
		
		$currentjobs = get_option('spjb_current_jobs');
		update_option('spjb_current_jobs', ($currentjobs-1));
		
		echo('<div id="spjb_yes">Job with the ID '.$id.' has been removed from the database</div>');
	}
	
	else if(!isset($_GET['id']) && (isset($_POST['type']) || isset($_POST['title']) || isset($_POST['wysiwyg']) || isset($_POST['boards']))){
		if($_POST['wysiwyg'] == ''){
			echo('<div id="spjb_no">Error: No content specified. Job not created.');
		}
		else{
			$id = get_option('spjb_currentjobid');
			$uploaddir = wp_upload_dir();
			
			$currentjobs = get_option('spjb_current_jobs');
			
			
			update_option('spjb_current_jobs', ($currentjobs+1));
			update_option('spjb_currentjobid', ($id+1));
			
			/* Create PDF */		
			
			$relhtmllink = $uploaddir['path']."/".$id."_".preg_replace("/[^a-zA-Z]+/", "_", $title).".html";
			$abshtmllink = $uploaddir['url']."/".$id."_".preg_replace("/[^a-zA-Z]+/", "_", $title).".html";
			
			$relpdflink = $uploaddir['path']."/".$id."_".preg_replace("/[^a-zA-Z]+/", "_", $title).".pdf";
			$abspdflink = $uploaddir['url']."/".$id."_".preg_replace("/[^a-zA-Z]+/", "_", $title).".pdf";
			
			require_once('includes/spjb_createpdf.php');
			require_once('includes/spjb_createhtml.php');
			createpdf($id, $type, $title, $content, $relpdflink);
			createhtml($id, $type, $title, $content, $relhtmllink);
			
			$wpdb->insert( 
				$tablename, 
				array( 
					'id' => $id, 
					'type' => $type, 
					'title' => $title,
					'content' => $content,
					'htmllink' => $abshtmllink,
					'relhtmllink' => $relhtmllink,
					'pdflink' => $abspdflink,
					'relpdflink' => $relpdflink,
					'applylink' => $applylink,
					'boards' => $boards
				) 
				
			);
					
			echo('<div id="spjb_yes">Successfully added new job to database</div>');
			echo('Click here to <a href="'.admin_url('admin.php?page=spjb-add-job', 'http').'&id='.$id.'">edit the created job.</a><br />');
			echo('View created HTML file: <a href="'.$abshtmllink.'">[HTML]</a> | View created PDF file: <a href="'.$abspdflink.'">[PDF]</a>');
		}
	}
	
	else{
		if((isset($_POST['type']) || isset($_POST['title']) || isset($_POST['wysiwyg']) || isset($_POST['boards'])) && $_POST['hidden']){
			$id = $hidden;	
			
			$query = "SELECT relhtmllink, relpdflink FROM ".$tablename." WHERE id = ".$id;
			$result = $wpdb->get_row($query);
			
			if(file_exists($result->relpdflink)){
				unlink($result->relpdflink);
			}
			
			if(file_exists($result->relhtmllink)){
				unlink($result->relhtmllink);
			}
			
			$uploaddir = wp_upload_dir();
			
			$relhtmllink = $uploaddir['path']."/".$id."_".preg_replace("/[^a-zA-Z]+/", "_", $title).".html";
			$abshtmllink = $uploaddir['url']."/".$id."_".preg_replace("/[^a-zA-Z]+/", "_", $title).".html";
			
			$relpdflink = $uploaddir['path']."/".$id."_".preg_replace("/[^a-zA-Z]+/", "_", $title).".pdf";
			$abspdflink = $uploaddir['url']."/".$id."_".preg_replace("/[^a-zA-Z]+/", "_", $title).".pdf";
			
			require_once('includes/spjb_createpdf.php');
			require_once('includes/spjb_createhtml.php');
			createpdf($id, $type, $title, $content, $relpdflink);
			createhtml($id, $type, $title, $content, $relhtmllink);
					
			$wpdb->update( 
				$tablename, 
				array( 
					'type' => $type, 
					'title' => $title,
					'content' => $content,
					'htmllink' => $abshtmllink,
					'relhtmllink' => $relhtmllink,
					'pdflink' => $abspdflink,
					'relpdflink' => $relpdflink,
					'applylink' => $applylink,
					'boards' => $boards
				), 
				array(
					'id' => $id
				)
			);
			
			echo('<div id="spjb_yes">Successfully updated job: '.$id.' / '.$title.' <br /></div>');
			echo('View created HTML file: <a href="'.$abshtmllink.'">[HTML]</a> | View created PDF file: <a href="'.$abspdflink.'">[PDF]</a>');
		}
		
		else if(isset($_GET['id']) && !isset($_POST['hidden'])){
			$id = $_GET['id'];
			$results = $wpdb->get_row('SELECT * FROM '.$tablename.' WHERE id='.$id.'');
			$type = $results->type;
			$title = $results->title;
			$content = $results->content;
			$abshtmllink = $results->htmllink;
			$abspdflink = $results->pdflink;
			$applylink = $results->applylink;
			$boards = $results->boards;
			$hidden = $id;
			
			echo("Updating the following job: ".$id." / ".$title." <br />");
			echo('View created HTML file: <a href="'.$abshtmllink.'">[HTML]</a> | View created PDF file: <a href="'.$abspdflink.'">[PDF]</a>');
			?><br />
			If you would like to delete this job, please <a href="<? echo(admin_url('admin.php?page=spjb-add-job', 'http')."&id=".$id."&delete=true"); ?>">click here</a> (Attention: Can not be undone). <?
		}
		
	    ?>
	    <form name="addjob" method="post">
	    <select name="type" style="width:200px;">
	    	<option value=""></option>
	    	<?
	    	$types = get_option('spjb_types');
			
			/* load drop-down list and select option stored in database as default */
	    	foreach($types as $tip => $value){
			    (strcmp($value, $type) == 0)? $selected = 'selected="true"' : $selected ="";
	    		$type = htmlspecialchars($type);
				echo('<option value="'.$value.'"'.$selected.'>'.$value.'</option>');
	    	}
			?>
			
	    </select>Type<br />
	    <input name="title" type="text" value="<? echo($title); ?>" style="width:200px;" /> Titel<br />
		
		<div style="float: left; width: 70%;">
	    <? wp_editor( stripcslashes($content), 'wysiwyg' ); ?>
	    <input name="boards" type="text" value="<? echo($boards); ?>" style="width:200px;" /> Shortcodes / boards (use comma to seperate; defined shortcodes: <? echo(implode(', ', get_option('spjb_shortcodes'))); ?>)<br />
	    <input name="hidden" type="hidden" value="<? echo($hidden); ?>" />
	    <input type="submit" value="Save" />
	    </form>
		</div>

		<div style="float: right; width: 29%;">
	    Available templates (click to load):<br /><br />
	    <?
    	$tablename = $wpdb->prefix . 'spjb_text';
    	$results = $wpdb->get_results('SELECT id, title FROM '.$tablename.' ORDER BY id ASC', ARRAY_N);
		foreach($results as $result){
			echo '<input type="submit" value="'.$result[1].'" onclick="activate('.$result[0].');"> <br />';
		}
	    ?>
		
		</div>
		<div style="clear: both;"></div>
	    </div>
	<?
	getFooter();
	}
}

function spjb_load_admin_submenu_manage_jobs(){ ?>
	<div id="spjb_admin">
    <h2>Manage jobs</h2><?
    
	global $wpdb;
	$tablename = $wpdb->prefix.'spjb_jobs';
	$results = $wpdb->get_results('SELECT * FROM '.$tablename.' ORDER BY id ASC');
	?>
	<table>
		<tr>
			<td>ID</td>
			<td>Type</td>
			<td>Title</td>
			<td>Content</td>
			<td>Link to HTML</td>
			<td>Link to PDF</td>
			<td>Link to Apply</td>
			<td>Board shortcode</td>
		</tr>		
		<?
		foreach($results as $key => $row)
		{?>
			<tr>
				<td><? echo $row->id; ?></td>
				<td><? echo $row->type; ?></td>
				<td><? echo $row->title; ?></td>
				<td><? echo(substr($row->content, 0, 30)."..."); ?></td>
				<td><? echo $row->htmllink; ?></td>
				<td><? echo $row->pdflink; ?></td>
				<td><? echo $row->applylink; ?></td>
				<td><? echo $row->boards; ?></td>
				<td><a href="<? echo(admin_url('admin.php?page=spjb-add-job', 'http')."&id=".$row->id); ?>">Edit</a></td>
			</tr>
		<?}?>
		
	</table></div>
	
	<?
	getFooter();
}

function spjb_load_admin_submenu_add_template(){?>
	<div id="spjb_admin">
    <h2>Add / Edit template</h2><?
	
	global $wpdb;
	$tablename = $wpdb->prefix.'spjb_text';
	$editor_id = 'wysiwyg';
	
	$id = ((isset($_GET['id']))? $_GET['id'] : ""); 
	$title = ((isset($_POST['title']))? $_POST['title'] : ""); 
	$content = ((isset($_POST['wysiwyg']))? $_POST['wysiwyg'] : ""); 
	$hidden = ((isset($_POST['hidden']))? $_POST['hidden'] : "");
	
	if(isset($_GET['id']) && isset($_GET['delete'])){
		$id = $_GET['id'];
		$wpdb->query("DELETE FROM $tablename WHERE id = $id");
		
		$currenttemplates = get_option('spjb_current_templates');
		update_option('spjb_current_templates', ($currenttemplates-1));
		
		echo('<div id="spjb_yes">Template with the ID '.$id.' has been removed from the database</div>');
	}
	
	else{
	
		if((isset($_POST['title']) || isset($_POST['wysiwyg'])) && $_POST['hidden']){
			
			
			$wpdb->update( 
				$tablename, 
				array( 
					'id' => $id, 
					'title' => $title,
					'value' => $content
				), 
				array(
					'id' => $id
				)
			);
				
			echo('<div id="spjb_yes">Successfully updated template: '.$id.' / '.$title.'</div>');
		}
		
		else if(isset($_GET['id']) && !isset($_POST['hidden'])){
			$results = $wpdb->get_row('SELECT * FROM '.$tablename.' WHERE id='.$id.'');
			$title = $results->title;
			$content = $results->value;
			$hidden = $id;
			
			echo("Updating the following template: ".$id." / ".$title);
			?><br />
			
			If you would like to delete this template, please <a href="<? echo(admin_url('admin.php?page=spjb-add-template', 'http')."&id=".$id."&delete=true"); ?>">click here</a> (Attention: Can not be undone). <?
		}
		
		else if((isset($_POST['title']) || isset($_POST['wysiwyg'])) && !isset($_GET['id'])){
			$id = get_option('spjb_currenttemplateid');
			update_option('spjb_currenttemplateid', ($id+1));
			
			$currenttemplates = get_option('spjb_current_templates');
			update_option('spjb_current_templates', ($currenttemplates+1));
						
			$wpdb->insert( 
				$tablename, 
				array( 
					'id' => $id, 
					'title' => $title,
					'value' => $content,
				) 
			);
			echo('<div id="spjb_yes">Successfully added new template to the database.</div>');
		}
		?>
	
		<form name="texts" method="post">
		<input name="title" type="text" value="<? echo($title); ?>" style="width:200px;" />Template Name<br />
		<? wp_editor( stripslashes($content), 'wysiwyg' ); ?>
		<input name="hidden" type="hidden" value="<? echo($hidden); ?>" />
	    <input type="submit" value="Save" />
	    </form>
		
		</div><?
		getFooter();
	}
}

function spjb_load_admin_submenu_manage_templates(){?>
	<div id="spjb_admin">
    <h2>Add / Edit template</h2><?
	
	global $wpdb;
	$tablename = $wpdb->prefix.'spjb_text';
	$results = $wpdb->get_results('SELECT * FROM '.$tablename.' ORDER BY id ASC');
	?>
	
	<table>
		<tr>
			<td>ID</td>
			<td>Name</td>
			<td>Content</td>
		</tr>		
		<?
		foreach($results as $key => $row)
		{?>
			<tr>
				<td><? echo $row->id; ?></td>
				<td><? echo $row->title; ?></td>
				<td><? echo(substr($row->value, 0, 30)."..."); ?> </td>
				<td><a href="<? echo(admin_url('admin.php?page=spjb-add-template', 'http')."&id=".$row->id); ?>">Edit</a></td>
			</tr>
		<?}?>
		
	</table></div>
	<?
	getFooter();
}


function spjb_load_admin_submenu_settings(){?>
	<div id="spjb_admin">
		
	<?php
	if(isset($_POST['jobtype'])){
		if($_POST['jobtype'] != "" && $_POST['jobtype'] != ","){
			$new = array_map('trim', explode(',', $_POST['jobtype']));
			update_option('spjb_types', array_merge(get_option('spjb_types'), $new));
			echo('<div id="spjb_yes">Successfully added new job types.</div>');
		}
	} 
	
	else if(isset($_POST['shortcode'])){
		if($_POST['shortcode'] != "" && $_POST['shortcode'] != ","){
			$new = array_map('trim', explode(',', $_POST['shortcode']));
			update_option('spjb_shortcodes', array_merge(get_option('spjb_shortcodes'), $new));
			echo('<div id="spjb_yes">Successfully added new shortcodes.</div>');
		}
	}
	
	else if(isset($_GET['deletetype'])){
		$array = get_option('spjb_types');
		unset($array[$_GET['deletetype']]);
		$array = array_values($array);
		update_option('spjb_types', $array);
		echo('<div id="spjb_yes">Type sucessfully deleted.</div>');
	}

	else if(isset($_GET['deleteshortcode'])){
		$array = get_option('spjb_shortcodes');
		unset($array[$_GET['deleteshortcode']]);
		$array = array_values($array);
		update_option('spjb_shortcodes', $array);
		echo('<div id="spjb_yes">Shortcode sucessfully deleted.</div>');
	}
	
	?>
		
    <h2>Plugin settings</h2>
    <h3>Manage job types</h3>
    Define and delete various job types, for example Internship, Part- and Full-Time. Note: You can use commas if you wish to add more than one type at once, for example "Internship, Full-Time, Part-Time". <br />
    
    <? 
    $array = get_option('spjb_types');
	$iter = 0;
	foreach($array as $item){
		if($item != "" && $item != " " && $item != ","){
			if($iter != 0) echo(", ");
			echo($item.' <a href="'.admin_url('admin.php?page=spjb-settings', 'http').'&deletetype='.$iter.'">(Delete)</a>');
		}
		$iter++;
	}
    ?>

    <form name="jobtypes" method="post">
    <input name="jobtype" type="text" style="width:350px;" /> <input type="submit" value="Add type(s)" />
    </form>
    
    <h3>Manage job shortcodes</h3>
    You can define shortcodes for your job boards using this setting. This setting is for overview only, you do not need to initially add a job board in order to use it when creating a job or using a shortcode in the front end.<br />
    
    <? 
    $array = get_option('spjb_shortcodes');
	$iter = 0;
	foreach($array as $item){
		if($item != "" && $item != " " && $item != ","){
			if($iter != 0) echo(", ");
			echo($item.' <a href="'.admin_url('admin.php?page=spjb-settings', 'http').'&deleteshortcode='.$iter.'">(Delete)</a>');
		}
		$iter++;
	}
    ?>
    
    <form name="jobshortcodes" method="post">
    <input name="shortcode" type="text" style="width:350px;" /> <input type="submit" value="Add board(s)" />
    </form>
    </div> <?
	getFooter();
  
	
}


?>