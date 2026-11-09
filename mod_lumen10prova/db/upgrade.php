<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Upgrade.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
function xmldb_lumen10prova_upgrade($oldversion) {
 if($oldversion<2026100401)upgrade_mod_savepoint(true,2026100401,'lumen10prova');
 if($oldversion<2026100410){global $DB;$manager=$DB->get_manager();$table=new xmldb_table('lumen10prova');
  foreach(['modalenabled','paperstyle','answersheetenabled']as$name){$field=new xmldb_field($name,XMLDB_TYPE_INTEGER,'1',null,XMLDB_NOTNULL,null,'1');if(!$manager->field_exists($table,$field))$manager->add_field($table,$field);}
  $field=new xmldb_field('watermarkopacity',XMLDB_TYPE_NUMBER,'5, 3',null,XMLDB_NOTNULL,null,'0.06');if(!$manager->field_exists($table,$field))$manager->add_field($table,$field);
  upgrade_mod_savepoint(true,2026100410,'lumen10prova');
 }
 if($oldversion<2026100901){
  // Course/grade APIs cannot run while Moodle's upgrade flag is active.
  \core\task\manager::queue_adhoc_task(new \mod_lumen10prova\task\sync_owned_quizzes(),true);
  upgrade_mod_savepoint(true,2026100901,'lumen10prova');
 }
 if($oldversion<2026102101){global $DB;$manager=$DB->get_manager();
  $table=new xmldb_table('lumen10prova');$field=new xmldb_field('exitlock',XMLDB_TYPE_INTEGER,'1',null,XMLDB_NOTNULL,null,'0');if(!$manager->field_exists($table,$field))$manager->add_field($table,$field);
  $table=new xmldb_table('lumen10prova_live');
  foreach(['blockedat'=>[XMLDB_TYPE_INTEGER,'10','0'],'blockreason'=>[XMLDB_TYPE_CHAR,'32','none'],'securitygeneration'=>[XMLDB_TYPE_INTEGER,'10','1']]as$name=>$spec){$field=new xmldb_field($name,$spec[0],$spec[1],null,XMLDB_NOTNULL,null,$spec[2]);if(!$manager->field_exists($table,$field))$manager->add_field($table,$field);}
  upgrade_mod_savepoint(true,2026102101,'lumen10prova');
 }
 if($oldversion<2026110900)upgrade_mod_savepoint(true,2026110900,'lumen10prova');
 return true;
}
