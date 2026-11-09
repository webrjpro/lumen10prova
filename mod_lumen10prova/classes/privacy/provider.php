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
 * Provider.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lumen10prova\privacy;
defined('MOODLE_INTERNAL') || die();
use core_privacy\local\metadata\collection;
use core_privacy\local\request\{contextlist,approved_contextlist,userlist,approved_userlist,writer};
final class provider implements \core_privacy\local\metadata\provider,\core_privacy\local\request\plugin\provider,\core_privacy\local\request\core_userlist_provider {
 public static function get_metadata(collection $c): collection {
  $c->add_database_table('lumen10prova_gate', [
   'provaid'=>'privacy:metadata:provaid', 'userid'=>'privacy:metadata:userid',
   'releaseat'=>'privacy:metadata:releaseat', 'acceptedhash'=>'privacy:metadata:acceptedhash',
   'acceptedat'=>'privacy:metadata:acceptedat', 'codefails'=>'privacy:metadata:codefails',
   'codelocked'=>'privacy:metadata:codelocked',
  ], 'privacy:metadata:gate');
  $c->add_database_table('lumen10prova_live', [
   'provaid'=>'privacy:metadata:provaid', 'userid'=>'privacy:metadata:userid',
   'attemptid'=>'privacy:metadata:attemptid', 'candidate'=>'privacy:metadata:candidate',
   'lastseen'=>'privacy:metadata:lastseen', 'iphash'=>'privacy:metadata:iphash',
   'devicehash'=>'privacy:metadata:devicehash', 'blockedat'=>'privacy:metadata:blockedat',
   'blockreason'=>'privacy:metadata:blockreason', 'securitygeneration'=>'privacy:metadata:securitygeneration',
   'focuschanges'=>'privacy:metadata:focuschanges', 'reconnections'=>'privacy:metadata:reconnections',
   'ipchanges'=>'privacy:metadata:ipchanges', 'devicechanges'=>'privacy:metadata:devicechanges',
  ], 'privacy:metadata:live');
  $c->add_database_table('lumen10prova_log', [
   'provaid'=>'privacy:metadata:provaid', 'userid'=>'privacy:metadata:userid',
   'attemptid'=>'privacy:metadata:attemptid', 'actorid'=>'privacy:metadata:actorid',
   'kind'=>'privacy:metadata:kind', 'detail'=>'privacy:metadata:detail', 'timecreated'=>'privacy:metadata:timecreated',
  ], 'privacy:metadata:log');
  $c->add_external_location_link('institutional_ai', ['text'=>'privacy:metadata:aitext'], 'privacy:metadata:ai');
  $c->add_subsystem_link('core_cache', ['userid'=>'privacy:metadata:userid', 'provaid'=>'privacy:metadata:provaid'],
      'privacy:metadata:importpreview');
  return $c;
 }
 public static function get_contexts_for_userid(int $userid):contextlist {$list=new contextlist();foreach(['gate','live','log'] as $t){$where='d.userid=:userid';if($t==='log')$where.=' OR d.actorid=:actor';$params=['userid'=>$userid,'level'=>CONTEXT_MODULE,'name'=>'lumen10prova'];if($t==='log')$params['actor']=$userid;
  $list->add_from_sql('SELECT DISTINCT ctx.id FROM {context} ctx JOIN {course_modules} cm ON cm.id=ctx.instanceid JOIN {modules} md ON md.id=cm.module JOIN {lumen10prova_'.$t.'} d ON d.provaid=cm.instance WHERE ctx.contextlevel=:level AND md.name=:name AND ('.$where.')',$params);}return $list;}
 private static function instance(\context $ctx):int{if($ctx->contextlevel!==CONTEXT_MODULE)return 0;$cm=get_coursemodule_from_id('lumen10prova',$ctx->instanceid);return $cm?(int)$cm->instance:0;}
 public static function get_users_in_context(userlist $list){$id=self::instance($list->get_context());if(!$id)return;foreach(['gate','live','log'] as $t)$list->add_from_sql('userid','SELECT userid FROM {lumen10prova_'.$t.'} WHERE provaid=:id',['id'=>$id]);$list->add_from_sql('actorid','SELECT actorid FROM {lumen10prova_log} WHERE provaid=:id',['id'=>$id]);}
 public static function export_user_data(approved_contextlist $list){global $DB;$uid=$list->get_user()->id;foreach($list->get_contexts() as $ctx){$id=self::instance($ctx);if(!$id)continue;foreach(['gate','live','log'] as $t){$params=[$id,$uid];$where='provaid=? AND userid=?';if($t==='log'){$where='provaid=? AND (userid=? OR actorid=?)';$params[]=$uid;}$rows=$DB->get_records_select('lumen10prova_'.$t,$where,$params,'id');foreach($rows as $r){unset($r->iphash,$r->devicehash);if($r->userid!=$uid)$r->userid=0;if(isset($r->actorid)&&$r->actorid!=$uid)$r->actorid=0;}writer::with_context($ctx)->export_data(['Lumen10 Prova',$t],(object)['records'=>array_values($rows)]);}}}
 public static function delete_data_for_all_users_in_context(\context $ctx){global $DB;$id=self::instance($ctx);if(!$id)return;foreach(['gate','live','log'] as $t)$DB->delete_records('lumen10prova_'.$t,['provaid'=>$id]);}
 private static function erase(int $id,int $uid):void {global $DB;foreach(['gate','live','log'] as $t)$DB->delete_records('lumen10prova_'.$t,['provaid'=>$id,'userid'=>$uid]);$DB->set_field('lumen10prova_log','actorid',0,['provaid'=>$id,'actorid'=>$uid]);}
 public static function delete_data_for_user(approved_contextlist $list){foreach($list->get_contexts() as $ctx){$id=self::instance($ctx);if($id)self::erase($id,$list->get_user()->id);}}
 public static function delete_data_for_users(approved_userlist $list){$id=self::instance($list->get_context());if($id)foreach($list->get_userids() as $uid)self::erase($id,$uid);}
}
