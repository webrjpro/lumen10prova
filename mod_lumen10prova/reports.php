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
 * Reports.
 *
 * @package    mod_lumen10prova
 * @copyright  2026 Carlos Antonio de Oliveira Piquet
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');require_once($CFG->dirroot.'/mod/quiz/locallib.php');
$id=required_param('id',PARAM_INT);[$p,$cm,$course,$context]=\mod_lumen10prova\ui::load($id);
require_capability('mod/lumen10prova:monitor',$context);
$quizcm=get_coursemodule_from_instance('quiz',$p->quizid,$p->course,false,MUST_EXIST);
$message='';$invalid=false;
if($_SERVER['REQUEST_METHOD']==='POST'&&optional_param('photoref','',PARAM_RAW_TRIMMED)!=='') {
 require_sesskey();
 try {
  $ref=\mod_lumen10prova\source_marks::verify($p,required_param('photoref',PARAM_RAW_TRIMMED));
  $u=$DB->get_record('user',['id'=>$ref['userid']],'id,firstname,lastname',MUST_EXIST);
  $message=get_string('photovalid','mod_lumen10prova',(object)['name'=>fullname($u),'attempt'=>$ref['attempt'],'slot'=>$ref['slot'],'started'=>userdate($ref['started'])]);
 } catch(moodle_exception $e) { $message=get_string('photoinvalid','mod_lumen10prova');$invalid=true; }
}
$links=[];foreach(['overview','statistics','responses']as$mode)$links[]=\mod_lumen10prova\ui::action('/mod/quiz/report.php',['id'=>$quizcm->id,'mode'=>$mode],get_string('report'.$mode,'mod_lumen10prova'));
$grades=array_map('floatval',$DB->get_fieldset_select('quiz_grades','grade','quiz=?',[$p->quizid]));sort($grades);$n=count($grades);$stats='';
if($n) {
 $median=$n%2?$grades[intdiv($n,2)]:($grades[$n/2-1]+$grades[$n/2])/2;
 $stats=get_string('gradestats','mod_lumen10prova',(object)['count'=>$n,'mean'=>format_float(array_sum($grades)/$n,2),
  'median'=>format_float($median,2),'min'=>format_float($grades[0],2),'max'=>format_float(end($grades),2)]);
}
$aid=optional_param('attempt',0,PARAM_INT);$where='provaid=?';$params=[$p->id];
if($aid) {$DB->get_record('quiz_attempts',['id'=>$aid,'quiz'=>$p->quizid],'id',MUST_EXIST);$where.=' AND attemptid=?';$params[]=$aid;}
$logs=$DB->get_records_select('lumen10prova_log',$where,$params,'id DESC','*',0,300);
$ids=array_values(array_unique(array_filter(array_column($logs,'userid'))));
$users=$ids?$DB->get_records_list('user','id',$ids,'','id,'.implode(',',\core_user\fields::get_name_fields())):[];
$rows=[];foreach($logs as$r)$rows[]=['date'=>userdate($r->timecreated),'user'=>isset($users[$r->userid])?fullname($users[$r->userid]):get_string('institution','mod_lumen10prova'),
 'attempt'=>$r->attemptid,'kind'=>$r->kind,'detail'=>$r->detail];
\mod_lumen10prova\ui::header($p,$cm,$course,'reports');
\mod_lumen10prova\ui::render('reports',['id'=>$id,'sesskey'=>sesskey(),'message'=>$message,'invalid'=>$invalid,'links'=>$links,'stats'=>$stats,'logs'=>$rows]);
\mod_lumen10prova\ui::footer();
