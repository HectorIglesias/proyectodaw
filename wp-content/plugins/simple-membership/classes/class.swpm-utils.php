<?php

abstract class SwpmUtils {

    public static function is_ajax() {
        return defined('DOING_AJAX') && DOING_AJAX;
    }

    public static function subscription_type_dropdown($selected) {
        return '<option ' . (($selected == SwpmMembershipLevel::NO_EXPIRY) ? 'selected="selected"' : "") . ' value="' . SwpmMembershipLevel::NO_EXPIRY . '">No Expiry</option>' .
                '<option ' . (($selected == SwpmMembershipLevel::DAYS) ? 'selected="selected"' : "") . ' value="' . SwpmMembershipLevel::DAYS . '">Day(s)</option>' .
                '<option ' . (($selected == SwpmMembershipLevel::WEEKS) ? 'selected="selected"' : "") . ' value="' . SwpmMembershipLevel::WEEKS . '">Week(s)</option>' .
                '<option ' . (($selected == SwpmMembershipLevel::MONTHS) ? 'selected="selected"' : "") . ' value="' . SwpmMembershipLevel::MONTHS . '">Month(s)</option>' .
                '<option ' . (($selected == SwpmMembershipLevel::YEARS) ? 'selected="selected"' : "") . ' value="' . SwpmMembershipLevel::YEARS . '">Year(s)</option>' .
                '<option ' . (($selected == SwpmMembershipLevel::FIXED_DATE) ? 'selected="selected"' : "") . ' value="' . SwpmMembershipLevel::FIXED_DATE . '">Fixed Date</option>';
    }

    // $subscript_period must be integer.
    public static function calculate_subscription_period_days($subcript_period, $subscription_duration_type) {
        if ($subscription_duration_type == SwpmMembershipLevel::NO_EXPIRY) {
            return 'noexpire';
        }
        if (!is_numeric($subcript_period)) {
            throw new Exception(" subcript_period parameter must be integer in SwpmUtils::calculate_subscription_period_days method");
        }
        switch (strtolower($subscription_duration_type)) {
            case SwpmMembershipLevel::DAYS:
                break;
            case SwpmMembershipLevel::WEEKS:
                $subcript_period = $subcript_period * 7;
                break;
            case SwpmMembershipLevel::MONTHS:
                $subcript_period = $subcript_period * 30;
                break;
            case SwpmMembershipLevel::YEARS:
                $subcript_period = $subcript_period * 365;
                break;
        }
        return $subcript_period;
    }

    public static function get_expiration_timestamp($user) {
        $permission = SwpmPermission::get_instance($user->membership_level);
        if (SwpmMembershipLevel::FIXED_DATE == $permission->get('subscription_duration_type')) {
            return strtotime($permission->get('subscription_period'));
        }
        $days = self::calculate_subscription_period_days(
                        $permission->get('subscription_period'), $permission->get('subscription_duration_type'));
        if ($days == 'noexpire') {
            return PHP_INT_MAX; // which is equivalent to
        }
        return strtotime($user->subscription_starts . ' ' . $days . ' days');
    }

    public static function is_subscription_expired($user) {
        $expiration_timestamp = SwpmUtils::get_expiration_timestamp($user);
        return $expiration_timestamp < time();
    }

    public static function gender_dropdown($selected = 'not specified') {
        return '<option ' . ((strtolower($selected) == 'male') ? 'selected="selected"' : "") . ' value="male">Male</option>' .
                '<option ' . ((strtolower($selected) == 'female') ? 'selected="selected"' : "") . ' value="female">Female</option>' .
                '<option ' . ((strtolower($selected) == 'not specified') ? 'selected="selected"' : "") . ' value="not specified">Not Specified</option>';
    }

    public static function get_account_state_options() {
        return array('active' => SwpmUtils::_('Active'),
            'inactive' => SwpmUtils::_('Inactive'),
            'pending' => SwpmUtils::_('Pending'),
            'expired' => SwpmUtils::_('Expired'),);
    }

    public static function account_state_dropdown($selected = 'active') {
        $options = self::get_account_state_options();
        $html = '';
        foreach ($options as $key => $value) {
            $html .= '<option ' . ((strtolower($selected) == $key) ? 'selected="selected"' : "") . '  value="' . $key . '"> ' . $value . '</option>';
        }
        return $html;
    }

    public static function membership_level_dropdown($selected = 0) {
        $options = '';
        global $wpdb;
        $query = "SELECT alias, id FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE id != 1";
        $levels = $wpdb->get_results($query);
        foreach ($levels as $level) {
            $options .= '<option ' . ($selected == $level->id ? 'selected="selected"' : '') . ' value="' . $level->id . '" >' . $level->alias . '</option>';
        }
        return $options;
    }

    public static function get_all_membership_level_ids() {
        global $wpdb;
        $query = "SELECT id FROM " . $wpdb->prefix . "swpm_membership_tbl WHERE id != 1";
        return $wpdb->get_col($query);
    }
    
    public static function membership_level_id_exists($level_id){
        //Returns true if the specified membership level exists in the system. Returns false if the level has been deleted (or doesn't exist).
        $all_level_ids = SwpmUtils::get_all_membership_level_ids();
        if (in_array($level_id, $all_level_ids)) {
            //Valid level ID
            return true;
        } else {
            return false;
        }
    }
    
    public static function get_registration_link($for = 'all', $send_email = false, $member_id = '') {
        $members = array();
        global $wpdb;
        switch ($for) {
            case 'one':
                if (empty($member_id)) {
                    return array();
                }
                $query = $wpdb->prepare("SELECT * FROM  {$wpdb->prefix}swpm_members_tbl WHERE member_id =  %d", $member_id);
                $members = $wpdb->get_results($query);
                break;
            case 'all':
                $query = "SELECT * FROM  {$wpdb->prefix}swpm_members_tbl WHERE reg_code != '' ";
                $members = $wpdb->get_results($query);
                break;
        }
        $settings = SwpmSettings::get_instance();
        $separator = '?';
        $url = $settings->get_value('registration-page-url');
        if (strpos($url, '?') !== false) {
            $separator = '&';
        }
        $subject = $settings->get_value('reg-complete-mail-subject');
        if (empty($subject)) {
            $subject = "Please complete your registration";
        }
        $body = $settings->get_value('reg-complete-mail-body');
        if (empty($body)) {
            $body = "Please use the following link to complete your registration. \n {reg_link}";
        }
        $from_address = $settings->get_value('email-from');
        $links = array();
        foreach ($members as $member) {
            $reg_url = $url . $separator . 'member_id=' . $member->member_id . '&code=' . $member->reg_code;
            if (!empty($send_email) && empty($member->user_name)) {
                $tags = array("{first_name}", "{last_name}", "{reg_link}");
                $vals = array($member->first_name, $member->last_name, $reg_url);
                $body = html_entity_decode($body);
                $email_body = str_replace($tags, $vals, $body);
                $headers = 'From: ' . $from_address . "\r\n";
                wp_mail($member->email, $subject, $email_body, $headers);
            }
            $links[] = $reg_url;
        }
        return $links;
    }

    public static function update_wp_user_Role($wp_user_id, $role) {
        $preserve_role = 'yes';
        if ($preserve_role) {
            return;
        }
        if (self::is_multisite_install()) {//MS install
            return; //TODO - don't do this for MS install
        }
        $caps = get_user_meta($wp_user_id, 'wp_capabilities', true);
        if (in_array('administrator', array_keys((array) $caps))) {
            return;
        }
        do_action('set_user_role', $wp_user_id, $role); //Fire the action for other plugin(s)
        wp_update_user(array('ID' => $wp_user_id, 'role' => $role));
        $roles = new WP_Roles();
        $level = $roles->roles[$role]['capabilities'];
        if (isset($level['level_10']) && $level['level_10']) {
            update_user_meta($wp_user_id, 'wp_user_level', 10);
            return;
        }
        if (isset($level['level_9']) && $level['level_9']) {
            update_user_meta($wp_user_id, 'wp_user_level', 9);
            return;
        }
        if (isset($level['level_8']) && $level['level_8']) {
            update_user_meta($wp_user_id, 'wp_user_level', 8);
            return;
        }
        if (isset($level['level_7']) && $level['level_7']) {
            update_user_meta($wp_user_id, 'wp_user_level', 7);
            return;
        }
        if (isset($level['level_6']) && $level['level_6']) {
            update_user_meta($wp_user_id, 'wp_user_level', 6);
            return;
        }
        if (isset($level['level_5']) && $level['level_5']) {
            update_user_meta($wp_user_id, 'wp_user_level', 5);
            return;
        }
        if (isset($level['level_4']) && $level['level_4']) {
            update_user_meta($wp_user_id, 'wp_user_level', 4);
            return;
        }
        if (isset($level['level_3']) && $level['level_3']) {
            update_user_meta($wp_user_id, 'wp_user_level', 3);
            return;
        }
        if (isset($level['level_2']) && $level['level_2']) {
            update_user_meta($wp_user_id, 'wp_user_level', 2);
            return;
        }
        if (isset($level['level_1']) && $level['level_1']) {
            update_user_meta($wp_user_id, 'wp_user_level', 1);
            return;
        }
        if (isset($level['level_0']) && $level['level_0']) {
            update_user_meta($wp_user_id, 'wp_user_level', 0);
            return;
        }
    }

    public static function update_wp_user($wp_user_name, $swpm_data) {
        $wp_user_info = array();
        if (isset($swpm_data['email'])) {
            $wp_user_info['user_email'] = $swpm_data['email'];
        }
        if (isset($swpm_data['first_name'])) {
            $wp_user_info['first_name'] = $swpm_data['first_name'];
        }
        if (isset($swpm_data['last_name'])) {
            $wp_user_info['last_name'] = $swpm_data['last_name'];
        }
        if (isset($swpm_data['plain_password'])) {
            $wp_user_info['user_pass'] = $swpm_data['plain_password'];
        }

        $wp_user = get_user_by('login', $wp_user_name);

        if ($wp_user) {
            $wp_user_info['ID'] = $wp_user->ID;
            return wp_update_user($wp_user_info);
        }
        return false;
    }

    public static function create_wp_user($wp_user_data) {
        if (self::is_multisite_install()) {//MS install
            global $blog_id;
            if ($wp_user_id = email_exists($wp_user_data['user_email'])) {// if user exists then just add him to current blog.
                add_existing_user_to_blog(array('user_id' => $wp_user_id, 'role' => 'subscriber'));
                return $wp_user_id;
            }
            $wp_user_id = wpmu_create_user($wp_user_data['user_login'], $wp_user_data['password'], $wp_user_data['user_email']);
            $role = 'subscriber'; //TODO - add user as a subscriber first. The subsequent update user role function to update the role to the correct one
            add_user_to_blog($blog_id, $wp_user_id, $role);
        } else {//Single site install
            $wp_user_id = email_exists($wp_user_data['user_email']);
            if ($wp_user_id) {
                return $wp_user_id;
            }
            $wp_user_id = wp_create_user($wp_user_data['user_login'], $wp_user_data['password'], $wp_user_data['user_email']);
        }
        $wp_user_data['ID'] = $wp_user_id;
        wp_update_user($wp_user_data);
        $user_info = get_userdata($wp_user_id);
        $user_cap = (isset($user_info->wp_capabilities) && is_array($user_info->wp_capabilities)) ? array_keys($user_info->wp_capabilities) : array();
        if (!in_array('administrator', $user_cap)) {
            SwpmUtils::update_wp_user_Role($wp_user_id, $wp_user_data['role']);
        }
        return $wp_user_id;
    }

    public static function is_multisite_install() {
        if (function_exists('is_multisite') && is_multisite()) {
            return true;
        } else {
            return false;
        }
    }

    public static function _($msg) {
        return __($msg, 'swpm');
    }

    public static function e($msg) {
        _e($msg, 'swpm');
    }

    public static function is_admin() {
        return current_user_can('manage_options');
    }

    public static function get_expire_date($start_date, $subscription_duration, $subscription_duration_type) {
        if ($subscription_duration_type == SwpmMembershipLevel::FIXED_DATE) { 
            //Membership will expire after a fixed date.
            return SwpmUtils::get_formatted_date_according_to_wp_settings($subscription_duration);
        }
        
        $expires = self::calculate_subscription_period_days($subscription_duration, $subscription_duration_type);
        if ($expires == 'noexpire') {
            //Membership is set to no expiry or until cancelled.
            return SwpmUtils::_('Never');
        }

        //Membership is set to a duration expiry settings.
        
        return date(get_option('date_format'), strtotime($start_date . ' ' . $expires . ' days'));
    }

    /* 
     * Formats the given date value according to the WP date format settings. This function is useful for displaying a human readable date value to the user.
     */
    public static function get_formatted_date_according_to_wp_settings($date){
        $date_format = get_option('date_format');
        if (empty($date_format)) {
            //WordPress's date form settings is not set. Lets set a default format.
            $date_format = 'Y-m-d';
        }

        $date_obj = new DateTime($date);
        $formatted_date = $date_obj->format($date_format);//Format the date value using date format settings
        return $formatted_date; 
    }
    
    public static function swpm_username_exists($user_name) {
        global $wpdb;
        $member_table = $wpdb->prefix . 'swpm_members_tbl';
        $query = $wpdb->prepare('SELECT member_id FROM ' . $member_table . ' WHERE user_name=%s', sanitize_user($user_name));
        return $wpdb->get_var($query);
    }

    public static function get_free_level() {
        $encrypted = filter_input(INPUT_POST, 'level_identifier');
        global $wpdb;
        if (!empty($encrypted)) {
            return SwpmPermission::get_instance($encrypted)->get('id');
        }

        $is_free = SwpmSettings::get_instance()->get_value('enable-free-membership');
        $free_level = absint(SwpmSettings::get_instance()->get_value('free-membership-id'));

        return ($is_free) ? $free_level : null;
    }

    public static function is_paid_registration() {
        $member_id = filter_input(INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT);
        $code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
        if (!empty($member_id) && !empty($code)) {
            return true;
        }
        return false;
    }

    public static function get_paid_member_info() {
        $member_id = filter_input(INPUT_GET, 'member_id', FILTER_SANITIZE_NUMBER_INT);
        $code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
        global $wpdb;
        if (!empty($member_id) && !empty($code)) {
            $query = 'SELECT * FROM ' . $wpdb->prefix . 'swpm_members_tbl WHERE member_id= %d AND reg_code=%s';
            $query = $wpdb->prepare($query, $member_id, $code);
            return $wpdb->get_row($query);
        }
        return null;
    }

    public static function get_incomplete_paid_member_info_by_ip() {
        global $wpdb;
        $user_ip = SwpmUtils::get_user_ip_address();
        if (!empty($user_ip)) {
            //Lets check if a payment has been confirmed from this user's IP and the profile needs to be completed (where username is empty).
            $username = '';
            $query = "SELECT * FROM " . $wpdb->prefix . "swpm_members_tbl WHERE last_accessed_from_ip=%s AND user_name=%s";
            $query = $wpdb->prepare($query, $user_ip, $username);
            $result = $wpdb->get_row($query);
            return $result;
        }
        return null;
    }

    public static function account_delete_confirmation_ui($msg = "") {
        ob_start();
        include(SIMPLE_WP_MEMBERSHIP_PATH . 'views/account_delete_warning.php');
        ob_get_flush();
        wp_die("", "", array('back_link' => true));
    }

    public static function delete_account_button() {
        $allow_account_deletion = SwpmSettings::get_instance()->get_value('allow-account-deletion');
        if (empty($allow_account_deletion)) {
            return "";
        }

        $account_delete_link = '<div class="swpm-profile-account-delete-section">';
        $account_delete_link .= '<a href="'.SIMPLE_WP_MEMBERSHIP_SITE_HOME_URL.'/?swpm_delete_account=1"><div class="swpm-account-delete-button">' . SwpmUtils::_("Delete Account") . '</div></a>';
        $account_delete_link .= '</div>';        
        return $account_delete_link;
    }

    public static function encrypt_password($plain_password) {
        include_once(ABSPATH . WPINC . '/class-phpass.php');
        $wp_hasher = new PasswordHash(8, TRUE);
        $password_hash = $wp_hasher->HashPassword(trim($plain_password));
        return $password_hash;
    }

    public static function get_restricted_image_url() {
        return SIMPLE_WP_MEMBERSHIP_URL . '/images/restricted-icon.png';
    }

    /*
     * Checks if the string exists in the array key value of the provided array. If it doesn't exist, it returns the first key element from the valid values.
     */

    public static function sanitize_value_by_array($val_to_check, $valid_values) {
        $keys = array_keys($valid_values);
        $keys = array_map('strtolower', $keys);
        if (in_array($val_to_check, $keys)) {
            return $val_to_check;
        }
        return reset($keys); //Return he first element from the valid values
    }

    public static function get_user_ip_address() {
        $user_ip = '';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $user_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $user_ip = $_SERVER['REMOTE_ADDR'];
        }

        if (strstr($user_ip, ',')) {
            $ip_values = explode(',', $user_ip);
            $user_ip = $ip_values['0'];
        }

        return apply_filters('swpm_get_user_ip_address', $user_ip);
    }
    
    public static function is_first_click_free(&$content){        
        $is_first_click = false;        
        $args = array($is_first_click, $content );
        $filtered = apply_filters('swpm_first_click_free', $args);
        list($is_first_click, $content) = $filtered;
        return $is_first_click;       
    }

    
}
