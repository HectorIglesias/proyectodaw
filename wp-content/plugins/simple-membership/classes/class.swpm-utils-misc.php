<?php

class SwpmMiscUtils {

    public static function create_mandatory_wp_pages() {
        $settings = SwpmSettings::get_instance();

        //Create join us page
        $swpm_join_page_content = '<p style="color:red;font-weight:bold;">This page and the content has been automatically generated for you to give you a basic idea of how a "Join Us" page should look like. You can customize this page however you like it by editing this page from your WordPress page editor.</p>';
        $swpm_join_page_content .= '<p style="font-weight:bold;">If you end up changing the URL of this page then make sure to update the URL value in the settings menu of the plugin.</p>';
        $swpm_join_page_content .= '<p style="border-top:1px solid #ccc;padding-top:10px;margin-top:10px;"></p>
			<strong>Free Membership</strong>
			<br />
			You get unlimited access to free membership content
			<br />
			<em><strong>Price: Free!</strong></em>
			<br /><br />Link the following image to go to the Registration Page if you want your visitors to be able to create a free membership account<br /><br />
			<img title="Join Now" src="' . SIMPLE_WP_MEMBERSHIP_URL . '/images/join-now-button-image.gif" alt="Join Now Button" width="277" height="82" />
			<p style="border-bottom:1px solid #ccc;padding-bottom:10px;margin-bottom:10px;"></p>';
        $swpm_join_page_content .= '<p><strong>You can register for a Free Membership or pay for one of the following membership options</strong></p>';
        $swpm_join_page_content .= '<p style="border-top:1px solid #ccc;padding-top:10px;margin-top:10px;"></p>
			[ ==> Insert Payment Button For Your Paid Membership Levels Here <== ]
			<p style="border-bottom:1px solid #ccc;padding-bottom:10px;margin-bottom:10px;"></p>';

        $swpm_join_page = array(
            'post_title' => 'Join Us',
            'post_name' => 'membership-join',
            'post_content' => $swpm_join_page_content,
            'post_parent' => 0,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );

        $join_page_obj = get_page_by_path('membership-join');
        if (!$join_page_obj) {
            $join_page_id = wp_insert_post($swpm_join_page);
        } else {
            $join_page_id = $join_page_obj->ID;
            if ($join_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $join_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_join_page_permalink = get_permalink($join_page_id);
        $settings->set_value('join-us-page-url', $swpm_join_page_permalink);

        //Create registration page
        $swpm_rego_page = array(
            'post_title' => SwpmUtils::_('Registration'),
            'post_name' => 'membership-registration',
            'post_content' => '[swpm_registration_form]',
            'post_parent' => $join_page_id,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $rego_page_obj = get_page_by_path('membership-registration');
        if (!$rego_page_obj) {
            $rego_page_id = wp_insert_post($swpm_rego_page);
        } else {
            $rego_page_id = $rego_page_obj->ID;
            if ($rego_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $rego_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_rego_page_permalink = get_permalink($rego_page_id);
        $settings->set_value('registration-page-url', $swpm_rego_page_permalink);

        //Create login page
        $swpm_login_page = array(
            'post_title' => SwpmUtils::_('Member Login'),
            'post_name' => 'membership-login',
            'post_content' => '[swpm_login_form]',
            'post_parent' => 0,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $login_page_obj = get_page_by_path('membership-login');
        if (!$login_page_obj) {
            $login_page_id = wp_insert_post($swpm_login_page);
        } else {
            $login_page_id = $login_page_obj->ID;
            if ($login_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $login_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_login_page_permalink = get_permalink($login_page_id);
        $settings->set_value('login-page-url', $swpm_login_page_permalink);

        //Create profile page
        $swpm_profile_page = array(
            'post_title' => SwpmUtils::_('Profile'),
            'post_name' => 'membership-profile',
            'post_content' => '[swpm_profile_form]',
            'post_parent' => $login_page_id,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $profile_page_obj = get_page_by_path('membership-profile');
        if (!$profile_page_obj) {
            $profile_page_id = wp_insert_post($swpm_profile_page);
        } else {
            $profile_page_id = $profile_page_obj->ID;
            if ($profile_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $profile_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_profile_page_permalink = get_permalink($profile_page_id);
        $settings->set_value('profile-page-url', $swpm_profile_page_permalink);

        //Create reset page
        $swpm_reset_page = array(
            'post_title' => SwpmUtils::_('Password Reset'),
            'post_name' => 'password-reset',
            'post_content' => '[swpm_reset_form]',
            'post_parent' => $login_page_id,
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        $reset_page_obj = get_page_by_path('password-reset');
        if (!$profile_page_obj) {
            $reset_page_id = wp_insert_post($swpm_reset_page);
        } else {
            $reset_page_id = $reset_page_obj->ID;
            if ($reset_page_obj->post_status == 'trash') { //For cases where page may be in trash, bring it out of trash
                wp_update_post(array('ID' => $reset_page_obj->ID, 'post_status' => 'publish'));
            }
        }
        $swpm_reset_page_permalink = get_permalink($reset_page_id);
        $settings->set_value('reset-page-url', $swpm_reset_page_permalink);

        $settings->save(); //Save all settings object changes
    }

    public static function redirect_to_url($url) {
        if (empty($url)) {
            return;
        }
        $url = apply_filters('swpm_redirect_to_url', $url);

        if (!preg_match("/http/", $url)) {//URL value is incorrect
            echo '<p>Error! The URL value you entered in the plugin configuration is incorrect.</p>';
            echo '<p>A URL must always have the "http" keyword in it.</p>';
            echo '<p style="font-weight: bold;">The URL value you currently configured is: <br />' . $url . '</p>';
            echo '<p>Here are some examples of correctly formatted URL values for your reference: <br />http://www.example.com<br/>http://example.com<br />https://www.example.com</p>';
            echo '<p>Find the field where you entered this incorrect URL value and correct the mistake then try again.</p>';
            exit;
        }
        if (!headers_sent()) {
            header('Location: ' . $url);
        } else {
            echo '<meta http-equiv="refresh" content="0;url=' . $url . '" />';
        }
        exit;
    }

    public static function get_current_page_url() {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

    public static function replace_dynamic_tags($msg_body, $member_id, $additional_args='') {
        $settings = SwpmSettings::get_instance();
        $user_record = SwpmMemberUtils::get_user_by_id($member_id);

        $password = '';
        $reg_link = '';
        if (!empty($additional_args)) {
            $password = isset($additional_args['password']) ? $additional_args['password'] : $password;
            $reg_link = isset($additional_args['reg_link']) ? $additional_args['reg_link'] : $reg_link;
        }
        $login_link = $settings->get_value('login-page-url');
                
        //Define the replacable tags
        $tags = array("{member_id}", "{user_name}", "{first_name}", "{last_name}", "{membership_level}",
            "{account_state}", "{email}", "{phone}", "{member_since}", "{subscription_starts}", "{company_name}", 
            "{password}", "{login_link}", "{reg_link}"
        );
    
        //Define the values
        $vals = array($member_id, $user_record->user_name, $user_record->first_name, $user_record->last_name, $user_record->membership_level,
            $user_record->account_state, $user_record->email, $user_record->phone, $user_record->member_since, $user_record->subscription_starts, $user_record->company_name,
            $password, $login_link, $reg_link
        );
    
        $msg_body = str_replace($tags, $vals, $msg_body);
        return $msg_body;
    }
    
    public static function get_login_link() {
        $login_url = SwpmSettings::get_instance()->get_value('login-page-url');
        $joinus_url = SwpmSettings::get_instance()->get_value('join-us-page-url');
        if (empty($login_url) || empty($joinus_url)) {
            return '<span style="color:red;">Simple Membership is not configured correctly. The login page or the join us page URL is missing in the settings configuration. '
                    . 'Please contact <a href="mailto:' . get_option('admin_email') . '">Admin</a>';
        }
        
        //Create the login/protection message
        $filtered_login_url = apply_filters('swpm_get_login_link_url', $login_url);//Addons can override the login URL value using this filter.
        $login_msg = '';
        $login_msg .= SwpmUtils::_('Please') . ' <a class="swpm-login-link" href="' . $filtered_login_url . '">' . SwpmUtils::_('Login') . '</a>. ';
        $login_msg .= SwpmUtils::_('Not a Member?') . ' <a href="' . $joinus_url . '">' . SwpmUtils::_('Join Us') . '</a>';
        
        return $login_msg;
    }

    public static function get_renewal_link() {
        $renewal = SwpmSettings::get_instance()->get_value('renewal-page-url');
        if (empty($renewal)) {
            //No renewal page is configured so don't show any renewal page link. It is okay to have no renewal page configured.
            return '';
        }
        return SwpmUtils::_('Please') . ' <a class="swpm-renewal-link" href="' . $renewal . '">' . SwpmUtils::_('renew') . '</a> ' . SwpmUtils::_(' your account to gain access to this content.');
    }
    
    public static function compare_url($url1, $url2){
        $url1 = trailingslashit(strtolower($url1));
        $url2 = trailingslashit(strtolower($url2));        
        if ($url1 == $url2) {return true;}        
        
        $url1 = parse_url($url1);
        $url2 = parse_url($url2); 
        
        $components = array('scheme','host','port','path');
        
        foreach ($components as $key=>$value){
            if (!isset($url1[$value])&& !isset($url2[$value])) {continue;}
            
            if (!isset($url2[$value])) {return false;}
            if (!isset($url1[$value])) {return false;}            
            
            if ($url1[$value] != $url2[$value]) {return false;}
        }

        if (!isset($url1['query'])&& !isset($url2['query'])) {return true;}

        if (!isset($url2['query'])) {return false;}
        if (!isset($url1['query'])) {return false;}            
            
        return strpos($url1['query'], $url2['query']) || strpos($url2['query'], $url1['query']);                
    }
}