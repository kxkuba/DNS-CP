<?php
/* lib/server/powerdns.server.class.php - DNS-CP
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
/* powerDNS server class */

class server extends dns_server {
	/* RECORD */
	public static function get_record ($domain, $record) {
		$conf = system::get_conf();
		$res = DB::query("SELECT * FROM ".$conf["rr"]." where id = :id and domain_id = :zone ", array(":zone" => $domain, ":id" => $record)) or die(DB::error());
		$records = DB::fetch_array($res);
		$return = array();
		/* make powerdns records compactible with our interface */
		$return['id'] = $records['id'];
		$return['zone'] = $records['domain_id'];
		$return['name'] = $records['name'];
		$return['data'] = $records['content'];
		$return['aux'] = $records['prio'];
		$return['ttl'] = $records['ttl'];
		$return['type'] = $records['type'];
		return $return;
	}
	
	public static function add_record ($domain, $record) {
		$conf = system::get_conf();
		$bind = array(":zone" => $domain,":name" => $record['newhost'],":type" => $record['newtype'],":data" => $record['newdestination'],":aux" => $record['newpri'],":ttl" => $record['newttl'], ":date" => time());
		DB::query("INSERT INTO ".$conf["rr"]." (domain_id ,name ,type ,content ,ttl ,prio ,change_date) VALUES (:zone, :name, :type, :data, :ttl, :aux, :date)", $bind) or die(DB::error());
		parent::add_record($domain, $record);
		return true;
	}
	
	public static function del_record ($domain, $record) {
		$conf = system::get_conf();
		DB::query("DELETE FROM ".$conf["rr"]." WHERE id = :id and domain_id = :zone", array(":id" => $record, ":zone" => $domain)) or die(DB::error());
		parent::del_record($domain, $record);
		return true;
	}
	
	public static function set_record ($domain, $record) {
		$conf = system::get_conf();
		$bind = array(":name" => $record['host'],":type" => $record['type'],":aux" => $record['aux'],":data" => $record['destination'],":ttl" => $record['ttl'],":id" => $record['host_id'],":zone" => $domain, ":date" => time());
		DB::query("UPDATE ".$conf["rr"]." SET name = :name, type = :type, content = :data, ttl = :ttl, prio = :aux, change_date = :date WHERE id = :id and domain_id = :zone", $bind) or die(DB::error());	
		parent::set_record($domain, $record);
		return true;
	}
	
	public static function get_all_records ($domain) {
		$conf = system::get_conf();
		$res = DB::query("SELECT * FROM ".$conf["rr"]." where domain_id = :zone ", array(":zone" => $domain)) or die(DB::error());
		$return = array();
		while($row = DB::fetch_array($res)) {
			if($row['type'] == "SOA") continue; /* skip soa record */
			$change = array();
			/* make powerdns records compactible with our interface */
			$change['id'] = $row['id'];
			$change['zone'] = $row['domain_id'];
			$change['name'] = $row['name'];
			$change['data'] = $row['content'];
			$change['aux'] = $row['prio'];
			$change['ttl'] = $row['ttl'];
			$change['type'] = $row['type'];
			$return[] = $change;
		}
		return $return;
	}

	/* ZONE */
	public static function get_zone ($domain, $owner = Null, $api = false) {
		$conf = system::get_conf();
		if(user::isAdmin() or $api) {
			$re = DB::query("SELECT * FROM ".$conf["soa"]." where id = :id and owner = :owner", array(":id" => $domain, ":owner" => $owner));
			$row = DB::fetch_array($re);
			$res = DB::query("SELECT * FROM ".$conf["rr"]." where domain_id = :id and type = :type", array(":id" => $domain, ":type" => "SOA")) or die(DB::error());
		} else {
			$re = DB::query("SELECT * FROM ".$conf["soa"]." where id = :id and owner = :owner", array(":id" => $domain, ":owner" => $owner));
			$row = DB::fetch_array($re);
			if($row['id'] == $domain) {
				$res = DB::query("SELECT * FROM ".$conf["rr"]." where domain_id = :id and type = :type", array(":id" => $domain, ":type" => "SOA")) or die(DB::error());
			} else {
				/* create a null returning query */
				$res = DB::query("SELECT * FROM ".$conf["rr"]." where domain_id = :id and type = :type", array(":id" => '0', ":type" => "EMPTYQUERY")) or die(DB::error());
			}
		}
		parent::get_zone($domain, $owner, $api);
		$zone = DB::fetch_array($res);
		$return = array();
		/* make powerdns soa compactible with our interface */
		$return['id'] = $zone['domain_id'];
		$return['origin'] = $zone['name'];
		$content = explode(" ", $zone['content']);
		if(count($content) == 7) {
			$return['ns'] = $content[0];
			$return['mbox'] = $content[1];
			$return['serial'] = $content[2];
			$return['refresh'] = $content[3];
			$return['retry'] = $content[4];
			$return['expire'] = $content[5];
			$return['minimum'] = $content[6];
		}
		$return['ttl'] = $zone['ttl'];
		$return['owner'] = $row['owner'];
		return $return;
	}
		
	public static function add_zone ($domain, $owner = Null) {
		$conf = system::get_conf();
		if(empty($owner)) {
			DB::query("INSERT INTO ".$conf["soa"]." (name, master, last_check, type, notified_serial, account, owner) VALUES (:zone, NULL, NULL, 'MASTER', NULL, NULL, 0);", array(":zone" => $domain));
		} else {
			DB::query("INSERT INTO ".$conf["soa"]." (name, master, last_check, type, notified_serial, account, owner) VALUES (:zone, NULL, NULL, 'MASTER', NULL, NULL, :owner);", array(":zone" => $domain, ":owner" => $owner));
		}
		$content = $conf["soans"]." ".$conf["mbox"]." ".date("Ymd")."01 ".$conf["refresh"]." ".$conf["retry"]." ".$conf["expire"]." ".$conf["minimum_ttl"];
		$bind = array(":id" => DB::last_id(), ":name" => $domain, ":type" => "SOA", ":content" => $content, ":ttl" => $conf["ttl"], ":prio" => 0, ":date" => time());
		DB::query("INSERT INTO ".$conf["rr"]." (domain_id, name, type, content, ttl, prio, change_date) VALUES (:id, :name, :type, :content, :ttl, :prio, :date);", $bind);
		parent::add_zone($domain, $owner);
		return true;
	}

	public static function del_zone ($domain) {
		$conf = system::get_conf();
		DB::query("DELETE FROM ".$conf["soa"]." WHERE id = :id", array(":id" => $domain)) or die(DB::error());
		DB::query("DELETE FROM ".$conf["rr"]." WHERE domain_id = :id", array(":id" => $domain)) or die(DB::error());
		parent::del_zone($domain);
		return true;
	}
	
	public static function set_zone ($domain, $data) {
		$conf = system::get_conf();

		$content = $conf["soans"]." ".$conf["mbox"]." ".$data['serial']." ".$data['refresh']." ".$data['retry']." ".$data['expire']." ".$conf["minimum_ttl"];
		DB::query("UPDATE ".$conf["rr"]." SET content = :content, ttl = :ttl where domain_id = :name ", array(":content" => $content, ":ttl" => $data['attl'], ":name" => $domain));
		if($data['owner'])
			DB::query("UPDATE ".$conf["soa"]." SET owner = :owner where id = :id ", array(":owner" => $data['owner'], ":id" => $domain));
		parent::set_zone($domain, $data);
		return true;
	}
	
	public static function get_all_zones ($owner = Null) {
		$conf = system::get_conf();
		if($owner) {
			$res = DB::query("SELECT * FROM ".$conf["soa"]." where owner = :owner", array(":owner" => $owner));
		} else {
			$res = DB::query("SELECT * FROM ".$conf["soa"]);
		}
		$return = array();
		while($row = DB::fetch_array($res)) {
			$res2 = DB::query("SELECT * FROM ".$conf["rr"]." where domain_id = :id and type = :type", array(":id" => $row['id'], ":type" => "SOA")) or die(DB::error());
			while($zone = DB::fetch_array($res2)) {
				$change=array();
				/* make powerdns soa compactible with our interface */
				$change['id'] = $row['id'];
				$change['origin'] = $zone['name'];
				$content = explode(" ", $zone['content']);
				$change['ns'] = $content[0];
				$change['mbox'] = $content[1];
				$change['serial'] = $content[2];
				$change['refresh'] = $content[3];
				$change['retry'] = $content[4];
				$change['expire'] = $content[5];
				$change['minimum'] = $content[6];
				$change['ttl'] = $zone['ttl'];
				$change['owner'] = $row['owner'];
				$return[] = $change;
			}
		}
		return $return;
	}

	public static function get_zone_by_name($name) {
		$conf = system::get_conf();
		$res = DB::query("SELECT * FROM ".$conf["soa"]." where name = :name", array(":name" => $name));
		$zone = DB::fetch_array($res);
		return self::get_zone($zone['id']);
	}
}
?>
