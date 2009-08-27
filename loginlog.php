<?php
/*
Plugin Name: Login Logger
Plugin URI:
Description: Log the most recent successful login for each user, as well as all unsuccessful logins
Version: 1.1
Author: Stephen Merriman
Author URI: http://www.cre8d-design.com

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation; either version 2 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA	02111-1307	USA
*/
$loginlog_db_version = "1.1";

function loginlog_install()
{
	global $wpdb;
	$table_name = $wpdb->prefix . "loginlog";
	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	$charset_collate='';
	if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";
	}
	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		$sql = "CREATE TABLE " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			username varchar(60) NOT NULL,
			time datetime NOT NULL,
			ip varchar(20) NOT NULL,
			success char(1) NOT NULL,
			active datetime NOT NULL
		) $charset_collate;";


		dbDelta($sql);

		add_option("loginlog_db_version",$loginlog_db_version);
	} else {
		$wpdb->query("ALTER TABLE $table_name $charset_collate");
	}
}

function loginlog_addlogin($user_login='', $user_pass='', $cookie=false)
{
	if ($_POST) {
	// check if it actually works

	global $wpdb;
	$using_cookie = wp_get_cookie_login();
	if (!empty($using_cookie)) $cookie = true;
	$success=0;

	if (wp_login($user_login, $user_pass, $cookie)) $success=1;

	$table_name = $wpdb->prefix . "loginlog";

	if ($success==1) {
		$delete = "DELETE FROM ".$table_name." WHERE username='".$user_login."' AND success='1'";
		$wpdb->query($delete);
	}
	$insert = "INSERT INTO " . $table_name . " (username,time,active,ip,success) VALUES ('".$user_login."','".current_time('mysql')."','".current_time('mysql')."','".$_SERVER['REMOTE_ADDR']."','".$success."')";
	$wpdb->query($insert);
	}
}
function loginlog_loginfailed($user_login) {
	global $wpdb;
	$table_name = $wpdb->prefix . "loginlog";
	$insert = "INSERT INTO " . $table_name . " (username,time,active,ip,success) VALUES ('".$user_login."','".current_time('mysql')."','".current_time('mysql')."','".$_SERVER['REMOTE_ADDR']."','0')";
	$wpdb->query($insert);
}
function loginlog_loginsuccess($user_login) {
	global $wpdb;
	$table_name = $wpdb->prefix . "loginlog";
	$delete = "DELETE FROM ".$table_name." WHERE username='".$user_login."' AND success='1'";
	$wpdb->query($delete);
	$insert = "INSERT INTO " . $table_name . " (username,time,active,ip,success) VALUES ('".$user_login."','".current_time('mysql')."','".current_time('mysql')."','".$_SERVER['REMOTE_ADDR']."','1')";
	$wpdb->query($insert);
}

function loginlog_users()
{
	global $wp_version;
	if ($wp_version<"2.1")
		add_submenu_page('profile.php','Login logs','Login logs',8,'login-logger/manage.php');
	else add_submenu_page('users.php','Login logs','Login logs',8,'login-logger/manage.php');
}
function loginlog_active() {
	global $user_login, $wpdb;
	$table_name = $wpdb->prefix."loginlog";
	$update = "UPDATE ".$table_name." SET active = '".current_time('mysql')."' WHERE username = '".$user_login."'";
	$wpdb->query($update);
}

global $wp_version;
if ($wp_version<"2.5") {
	add_action('wp_authenticate','loginlog_addlogin',10,2);
}
else {
	add_action('wp_login_failed','loginlog_loginfailed',10,1);
	add_action('wp_login','loginlog_loginsuccess',10,1);
}
add_action('admin_menu','loginlog_users');
add_action('activate_login-logger/loginlog.php','loginlog_install');
add_action('init','loginlog_active');
?>