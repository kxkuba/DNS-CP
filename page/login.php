<?php
/* page/login.php - DNS-WI
 * Copyright (C) 2013  OwnDNS project
 * http://owndns.me/
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License 
 * along with this program. If not, see <http://www.gnu.org/licenses/>. 
 */
if(!defined("IN_PAGE")) { die("no direct access allowed!"); }
if(isset($_POST["Submit"])) {
	$res = DB::query("SELECT * FROM ".$conf["users"]." WHERE username='".DB::escape($_POST["username"])."'") or die(DB::error());
	$row = DB::fetch_array($res);
	if($row["password"] == md5($_POST["password"])) {
		$_SESSION['login'] = 1;
		$_SESSION['username'] = $row["username"];
		$_SESSION['userid'] = $row["id"];
		$error = '<font color="#008000">Login sucessful</font>';
		$error .='<meta http-equiv="refresh" content="0; URL=?page=home">';
	} else {
		$error = '<font color="#ff0000">The data you have entered are invalid.</font>';
	}
} else { $error = ""; }
$data = array(
		"_name" => "Login",
		"_error" => $error
		);
$temp = template::get_template("login");
template::show($temp, $data);
?>