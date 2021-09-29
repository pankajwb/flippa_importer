<?php
/* Functions to add ninja form submission
*/
//add_filter('ninja_forms_submit_data', 'custom_ninja_forms_submit_data');
add_action('nd_register_preprocess', 'nd_register'); // nd_register needs to added as a wp hook in ninja form submission settings
function custom_ninja_forms_submit_data($form_data)
{
	$form_id       = $form_data[ 'form_id' ];
    $form_fields   =  $form_data[ 'fields' ];
    
    foreach( $form_fields as $field ){
        $field_id    = $field[ 'id' ];
        $field_key   = $field[ 'key' ];
        $field_value = $field[ 'value' ];
        if($field_key == 'preg_organisation_individual_name'){
            $ptitle = $field_value;
        }
        if($field_key == 'provider_type'){
            $provider_type = $field_value;
			$provided = $field_value;
//             $provided = '';
//             foreach($provider_type as $ptype){
//                 if($ptype !=' ' && $ptype != ''){ $provided .= $ptype.', ';}
//             }
        }
        if($field_key == 'preg_service_being_provided'){
            $services = $field_value;
        }
        if($field_key == 'preg_email'){
            $email = $field_value;
        }
        if($field_key == 'preg_mobile_number'){
            $mobile = $field_value;
        }
        if($field_key == 'preg_city'){
            $city = $field_value;
        }
		if($field_key == 'hidden_07state'){
            $ustate = $field_value;
        }
		if($field_key == 'hidden_08state'){
            $ustate = $field_value;
        }
        if($field_key == 'passwordconfirm_reg'){
            $pass = $field_value;
        }
        if($field_key == 'qualification_provider'){
            $qual = $field_value;
        }
        $row .= ' key = '.$field_key.' value = '.$field_value.'<br>'; 
        
    }

	$row .= $provided;
    $temppass = $pass;
    $temppasskey = base64_encode($temppass);
    $userdata = array(
        'user_login'    =>  $mobile,
        'user_email'    =>  $email,
        'user_pass'     =>  $temppass,
        //'user_url'      =>  'provider_registration',
        'first_name'    =>  $ptitle,
        'role' => 'provider'
        );

    $nuser = wp_insert_user( $userdata );
    $mailotp = mt_rand(1000,9999);
// 	$mobileind = '+91-'.$mobile;
	$mobileind = $mobile;
	update_user_meta( $nuser, 'mobile_number', $mobile );
	$d=strtotime("+ 48 hours");
	update_user_meta( $nuser, 'motp', $mailotp );
	update_user_meta( $nuser, 'otpvalidity', $d );
	
	update_user_meta( $nuser, 'wpcf-field_mobile', $mobileind ); // new added
	update_user_meta( $nuser, 'wpcf-user_city', $city ); // new added
	update_user_meta( $nuser, 'wpcf-user-state', $ustate ); // new added
	update_user_meta( $nuser, 'wpcf-provider-type', $provided ); // new added
	update_user_meta( $nuser, 'wpcf-provider-qualification', $qual ); // new added
	if($form_id == 7){
	  update_user_meta( $nuser, 'profile_type', 'individual' );  
	}elseif($form_id == 8){
	  update_user_meta( $nuser, 'profile_type', 'org' );  
	}
    $postdata = array(
        'post_type' =>'business',
//         'post_content' => $row,
        'post_status' => 'draft',
        'post_title' => $ptitle,
        'post_author' => $nuser,
        'post_name' => $ptitle
        );
    $pid = wp_insert_post($postdata);
    update_user_meta( $nuser, 'wpcf-provider-profile-id', $pid ); // new added
	
	wp_set_object_terms( $pid, $provider_type, 'business_category' );
	wp_set_object_terms( $pid, $city, 'location' );
	if($form_id == 7){
		wp_set_object_terms( $pid, 'Individual Professionals', 'type' );
	}elseif($form_id == 8){
		wp_set_object_terms( $pid, 'Organizations', 'type' );  
	}
	
    if ( ! add_post_meta( $pid, 'provider_type', $provided  ) ) { 
       update_post_meta ( $pid, 'provider_type', $provided  );
    }
    if ( ! add_post_meta( $pid, 'services', $services ) ) { 
       update_post_meta ( $pid, 'services', $services );
    }
    if ( ! add_post_meta( $pid, 'wpcf-email_id', $email ) ) { 
       update_post_meta ( $pid, 'wpcf-email_id', $email ); //email_id
    }
    if ( ! add_post_meta( $pid, 'wpcf-phone',$mobile  ) ) { 
		update_post_meta ( $pid, 'wpcf-phone',$mobile);
//        	update_post_meta ( $pid, 'wpcf-mobile_number',$mobile); //mobile_number 
		
    }
//     if ( ! add_post_meta( $pid, 'wpcf-provider-qualification',$qual  ) ) { 
//        update_post_meta ( $pid, 'wpcf-provider-qualification',$qual  );
//     }
	if (!empty($qual)) { 
       update_post_meta ( $pid, 'wpcf-qualification', $qual );
    }
    if ( ! add_post_meta( $pid, 'city',$city  ) ) { 
       update_post_meta ( $pid, 'city',$city  );
		
    }
	$url = site_url();
	$admin_email = get_option( 'admin_email' );
    $to = array($email,$admin_email);
    $subject = 'Nayi Disha provider registration';
//     $body = 'Please click here to verify your email: <a href="'.$url.'/verify-email?userid='.$nuser.'&uname='.$mobile.'&key='.$temppasskey.'">'.$url.'/verify-email?userid='.$nuser.'&uname='.$mobile.'&key=test123</a>';
//     $body .= '<br>';
//     $body .= ' For manual verification the OTP is '.$mailotp;
	$body = 'Dear Provider - Thank you for registering with Nayi Disha resource center. To complete your registration process please click on the below link:<br />';
	$body.='<a href="'.$url.'/verify-email?userid='.$nuser.'&uname='.$mobile.'&key='.$temppasskey.'">'.$url.'/verify-email?userid='.$nuser.'&uname='.$mobile.'&key=test123</a>';
    $body .= '<br>';
    $body .= 'Or enter the OTP '.$mailotp;
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    wp_mail( $to, $subject, $body, $headers );
}