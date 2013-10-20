<?php
/* lib/page/help.php - DNS-CP
 * Copyright (C) 2013  DNS-CP project
 * http://dns-cp-de/
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License 
 * along with this program. If not, see <http://www.gnu.org/licenses/>. 
 */

tpl::show("404", array(
		"name" => "404 - Not found",
		"content" => "<b>This page does not exist!</b>",
		"title" => $title,
		"login" => $login,
		"menu" => $tmenu,
		"build" => $conf["build"],
		"version" => $conf["version"]
		));
?>
