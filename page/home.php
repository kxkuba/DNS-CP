<?php
/* page/home.php - DNS-WI
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
$i = 0;
if(func::isAdmin()){
	$res = DB::query("SELECT * FROM ".$conf["soa"]) or die(DB::error());
} else {
	$res = DB::query("SELECT * FROM ".$conf["soa"]." WHERE owner = '".DB::escape($_SESSION['userid'])."'") or die(DB::error());
}
$i = DB::num_rows($res);

if(func::isAdmin()) { $status = "(<u>administrator</u>)"; } else { $status = "(<u>customer</u>)"; }
$data = array(
		"_name" => "Home",
		"_user" => $_SESSION['username'],
		"_status" => $status,
		"_zones" => $i
		);
$temp = template::get_template("home");
template::show($temp, $data);
?>
