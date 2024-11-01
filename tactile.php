<?php
/*
Plugin Name: Tactile CRM Contact Form
Plugin URI:  http://www.paulbain.co.uk/developer/wordpress/tactile-crm-contact-form
Description: Inject contact form information into Tactile CRM. You will need to go to <a href="options-general.php?page=tactilemanagepage">settings -> Tactile CRM</a> to set your API key and add a tag {tactile_contact} in any post/page.
Version: 1.3
Author: Paul Bain	
Author URI: http://www.paulbain.co.uk/developer/wordpress/tactile-crm-contact-form
*/


/*
 * tactile_content
 * Inert form and inject data into Tactile CRM
 */
function tactile_content($content){
	
	$site = get_option('tactile_site');
	$token = get_option('tactile_token');
	
	$regex = '/\{tactile_contact(.*?)}/i';
	preg_match_all( $regex, $content, $matches );
	
	$data = $_POST;

	$required = array(
		'firstname'=>"First Name is a required field",
		'surname' => "Surname is a required field",
		'email' => "Email address is a required field"
	);
	
	$replace;
	$errors = array();
	
	for($x=0; $x<count($matches[0]); $x++){

		if(empty($site) || empty($token)){
			$replace = 'Contact form not configured';
		} else {
			if(isset($data['tactile_submit'])){
				$errors = array();
				foreach($required as $r=>$msg){
					if(empty($_POST[$r])){
						$errors[$r] = $msg;
					}
				}
				if(empty($errors)){
					if(isset($matches[1][$x]) && !empty($matches[1][$x])){
						$tmp = trim($matches[1][$x]);
						$data['tags'] = explode(',',$tmp);
					}
					tactile_inject($data);
					$replace= 'Thank you, your request has been submitted and we will get back to you shortly.';	
				} 
			} 
			if(is_null($replace)){
				$replace = tactile_form($errors);
			}	
		}
		$content = str_replace($matches[0][$x],$replace,$content);
	}

	
	
	return $content;
	
}

/*
 * Produce Contact Form
 */
function tactile_form($errors = array()){

	$form="";
	if(!empty($errors)){
		$form.="<ul>";
		foreach($errors as $error=>$m){
			$form.="<li>$m</li>";
		}
		$form.="</ul>";
	}
	
	$form .=
	'<!--Tactile CRM Contact Form-->'. 
	'<div id="tactile">'.
	'<form action="'.get_permalink().'" method="POST">'.
	'<input type="text" name="nname" style="display:none"/>'.
	'<label for="firstname">First Name*</label><input type="text" name="firstname"/>'.
	'<label for="surname">Surname*</label><input type="text" name="surname"/>'.
	'<label for="company">Company</label><input type="text" name="company">'.
	'<label for="email">Email Address*</label><input type="text" name="email"/>'.
	'<label for="phone">Telephone Number</label><input type="text" name="phone"/>'.
	'<label for="website">Website</label><input type="text" name="website"/>'.
	'<label for="message">Message*</label><textarea name="message"></textarea>'.
	
	'<input type="submit" name="tactile_submit" value="Send Request" class="submit"/>'. 
	'<a href="http://www.tactilecrm.com" title="Tactile CRM"><img src="'.get_bloginfo('wpurl') . '/wp-content/plugins/tactile-crm-contact-form/tactile.png" alt="Tactile CRM"/></a>'.
	'</form>'.
	'</div>';
	
	return $form;
	
}


/*
 * Inject data into Tactile CRM
 */
function tactile_inject($data){
	require_once('client.php');
	global $post;
	$site = get_option('tactile_site');
	$token = get_option('tactile_token');
	
	$client = new Tactile_Json_Client($site,$token);
	
	
	if(isset($data['nname']) && !empty($data['nname'])){
		return false;
	}
	
	if(!empty($data['company'])){
		$json = array();
		$json['Organisation']['name'] = ucfirst($data['company']);
		$org=null;
		$orgs = $client->request("organisations",array('name'=>$data['company']));
		if($orgs->total){
			
			$org = $orgs->organisations[0];
		}
		
		if(!$org){
			if(!empty($data['website'])){
				$json['Organisation']['website'] = array('contact'=>$data['website']);
			}	
									
			$org = $client->request("organisations/save",null,$json);
			if(!$org || $org->status != 'success'){
				return false;
			}
			
			if(!empty($data['tags'])){
				foreach($data['tags'] as $tag){
					$client->request("organisations/add_tag/",array('id'=>$org->id, 'tag'=>$tag));
				}
			}
		}
	}
	
	$query=array(		'firstname'=>$data['firstname'],
							'surname'=>$data['surname']
				);
	
	$json = array();
	$json['Person'] = array(
							'firstname'=>$data['firstname'],
							'surname'=>$data['surname'],
							'email'=>array('contact'=>$data['email']),
						);
	if(!empty($data['phone'])){
		$json['Person']['phone'] = array('contact'=>$data['phone']);					
	}
	if($org){
		$json['Person']['organisation_id'] = $org->id;
		$query['organisation_id']=$org->id;
	}

	$person=null;
	$people = $client->request("people",$query);

	if($people->total){
		$person = $person->people[0];
	}
	if(!$person){
		// Create Person
		$person = $client->request("people/save",null,$json);
	}
	if(!$person || $person->status !='success'){
		return false;
	}
	
	if(!empty($data['tags'])){
		foreach($data['tags'] as $tag){
			$client->request("person/add_tag/",array('id'=>$person->id, 'tag'=>$tag));
		}
	}
	
	$json = array();
	$json['Activity'] = array(
							'name'=>'Respond to Website Contact',
							'class'=>'todo',
							'description'=>$data['message'],
							'person_id'=>$person->id,
							'later'=>true
						);
						
	if($org){
		$json['Activity']['organisation_id'] = $org->id;						
	}
	
	$activity = $client->request("activities/save",null,$json);
	
	
	$json = array();
	$json['Note'] = array(
					'title'=>'Website Contact',
					'note'=>$data['message']."\n\nPage: {$post->post_title}",
					'person_id'=>$person->id,
				);
	
	if($org){
		$json['Note']['organisation_id']=$org->id;				
	}
	if($activity && isset($activity->id)){
		$json['Note']['activity_id']=$activity->id;
	}
	$activity = $client->request("notes/save",null,$json);
	
	return true;

}


function tactile_header(){
	echo '<link type="text/css" rel="stylesheet" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/tactile-crm-contact-form/style.css" />' . "\n";
}

function tactile_manage_page() {

    if(isset($_POST['tactile_token']))
    {
       update_option('tactile_token', $_POST['tactile_token']);
       update_option('tactile_site', $_POST['tactile_site']);
    }
    
	tactile_preferences();
}



function tactile_preferences()
{?>
	<div class="wrap"><h2>Tactile CRM Preferences</h2>
		<form action="<?php $_SERVER['PHP_SELF'];?>" method="POST">
		<table class="edit-form">
		<tr>
			<td>Tactile CRM API Token:</td>
			<td><input name="tactile_token" value="<?php echo stripslashes(get_option('tactile_token'));?>"/></td>
		</tr>
		<tr>
			<td>Tactile CRM Site Address:</td>
			<td><input name="tactile_site" value="<?php echo stripslashes(get_option('tactile_site'));?>"/>.tactilecrm.com</td>
		</tr>		
		<tr>
			<td></td>
			<td><input type="submit" value="Save"></td>
		</tr>
		</table>
		</form>
	</div>
	
	<div class="wrap">
	<h3>Account Information</h3>
	<p>If you <b>do not</b> yet have a Tactile CRM account, head over to <a href="http://www.tactilecrm.com/signup/?source=WORDPRESS">www.tactilecrm.com</a> and use the code WORDPRESS when signing up.</p>
	<p>If you do have an existing account:</p>
	<ol>
		<li>Login to your Tactile CRM account as an admin, go to the admin link at the top right of the page.
		<li>Select "Toggle API access" and ensure the API is activated</li>
		<li>Go to your preferences click Show "API, Calendar, &amp; Email Dropbox Access" and copy the API key.</li>
	</ol>
	</div>
	<div class="wrap">
	<h3>Usage</h3>
	<p></p>
	<pre>
	# To insert a form into any post or page, just use the following tag:
	{tactile_contact}
	
	# Or to add tags when the data is inserted into Tactile CRM, use:
	{tactile_contact tag1,tag2,tag3}
	</pre>
	
	</div>
	<div class="wrap">
	<h3>Support &amp; Customization</h3>
	<p>
	If you need support or would like to know how to customize the form, please contact <a href="mailto:support@paulbain.co.uk">support@paulbain.co.uk</a>
	</p>
	</div>
	
<?php }




function tactile_add_admin()
{
	add_options_page('Tactile CRM', 'TactileCRM', 8, 'tactilemanagepage', 'tactile_manage_page');
}

add_action('admin_menu', 'tactile_add_admin');
add_filter('the_content','tactile_content');
add_filter('get_header','tactile_header');



?>
