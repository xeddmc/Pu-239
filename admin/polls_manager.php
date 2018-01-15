<?php
require_once INCL_DIR . 'user_functions.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $lang;

$lang = array_merge($lang, load_language('ad_poll_manager'));
$params = array_merge($_GET, $_POST);
$params['mode'] = isset($params['mode']) ? $params['mode'] : '';
$site_config['max_poll_questions'] = 2;
$site_config['max_poll_choices_per_question'] = 20;
switch ($params['mode']) {
    case 'delete':
        delete_poll();
        break;

    case 'edit':
        edit_poll_form();
        break;

    case 'new':
        show_poll_form();
        break;

    case 'poll_new':
        insert_new_poll();
        break;

    case 'poll_update':
        update_poll();
        break;

    default:
        show_poll_archive();
        break;
}
function delete_poll()
{
    global $site_config, $CURUSER, $cache, $lang;
    $total_votes = 0;
    if (!isset($_GET['pid']) or !is_valid_id($_GET['pid'])) {
        stderr($lang['poll_dp_usr_err'], $lang['poll_dp_no_poll']);
    }
    $pid = intval($_GET['pid']);
    if (!isset($_GET['sure'])) {
        stderr($lang['poll_dp_usr_warn'], "<h2>{$lang['poll_dp_forever']}</h2>
      <a href='javascript:history.back()' title='{$lang['poll_dp_cancel']}' style='color: green;font-weight: bold;'><span class='button is-small' style='padding:3px;'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_delete.gif' alt='{$lang['poll_dp_back']}' />{$lang['poll_dp_back']}</span></a>&#160;<a href=staffpanel.php?tool=polls_manager&amp;'action=polls_manager&amp;mode=delete&amp;pid={$pid}&amp;sure=1' title='{$lang['poll_dp_delete']}' style='color: green;font-weight: bold;'><span class='button is-small' style='padding:3px;'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_tick.gif' alt='{$lang['poll_dp_delete']}' />{$lang['poll_dp_delete2']}</span></a>");
    }
    sql_query('DELETE FROM polls WHERE pid = ' . sqlesc($pid));
    sql_query('DELETE FROM poll_voters WHERE poll_id = ' . sqlesc($pid));
    $cache->delete('poll_data_' . $CURUSER['id']);
    show_poll_archive();
}

function update_poll()
{
    global $site_config, $CURUSER, $cache, $lang;
    $total_votes = 0;
    if (!isset($_POST['pid']) or !is_valid_id($_POST['pid'])) {
        stderr($lang['poll_up_usr_err'], $lang['poll_up_no_poll']);
    }
    $pid = intval($_POST['pid']);
    if (!isset($_POST['poll_question']) or empty($_POST['poll_question'])) {
        stderr($lang['poll_up_usr_err'], $lang['poll_up_no_title']);
    }
    $poll_title = sqlesc(htmlsafechars(strip_tags($_POST['poll_question']), ENT_QUOTES));
    //get the main crux of the poll data
    $poll_data = makepoll();
    $total_votes = isset($poll_data['total_votes']) ? intval($poll_data['total_votes']) : 0;
    unset($poll_data['total_votes']);
    if (!is_array($poll_data) or !count($poll_data)) {
        stderr($lang['poll_up_sys_err'], $lang['poll_up_no_data']);
    }
    //all ok, serialize
    $poll_data = sqlesc(serialize($poll_data));
    $username = sqlesc($CURUSER['username']);
    sql_query("UPDATE polls SET choices = $poll_data, starter_id = {$CURUSER['id']}, starter_name = $username, votes = $total_votes, poll_question = $poll_title WHERE pid = " . sqlesc($pid)) or sqlerr(__FILE__, __LINE__);
    $cache->delete('poll_data_' . $CURUSER['id']);
    if (-1 == mysqli_affected_rows($GLOBALS['___mysqli_ston'])) {
        $msg = "<h2>{$lang['poll_up_error']}</h2>
      <a href='javascript:history.back()' title='{$lang['poll_up_fix_it']}' style='color: green;font-weight: bold;'><span class='button is-small' style='padding:3px;'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_delete.gif' alt='{$lang['poll_up_back']}' />{$lang['poll_up_back']}</span></a>";
    } else {
        $msg = "<h2>{$lang['poll_up_worked']}</h2>
      <a href='staffpanel.php?tool=polls_manager&amp;action=polls_manager' title='{$lang['poll_up_return']}' style='color: green;font-weight: bold;'><span class='button is-small' style='padding:3px;'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_tick.gif' alt='{$lang['poll_up_success']}' />{$lang['poll_up_success']}</span></a>";
    }
    echo stdhead($lang['poll_up_stdhead']) . $msg . stdfoot();
}

function insert_new_poll()
{
    global $site_config, $CURUSER, $cache, $lang;
    if (!isset($_POST['poll_question']) or empty($_POST['poll_question'])) {
        stderr($lang['poll_inp_usr_err'], $lang['poll_inp_no_title']);
    }
    $poll_title = sqlesc(htmlsafechars(strip_tags($_POST['poll_question']), ENT_QUOTES));
    //get the main crux of the poll data
    $poll_data = makepoll();
    if (!is_array($poll_data) or !count($poll_data)) {
        stderr($lang['poll_inp_sys_err'], $lang['poll_inp_no_data']);
    }
    //all ok, serialize
    $poll_data = sqlesc(serialize($poll_data));
    $username = sqlesc($CURUSER['username']);
    $time = TIME_NOW;
    sql_query("INSERT INTO polls (start_date, choices, starter_id, starter_name, votes, poll_question)VALUES($time, $poll_data, {$CURUSER['id']}, $username, 0, $poll_title)") or sqlerr(__FILE__, __LINE__);
    $cache->delete('poll_data_' . $CURUSER['id']);
    if (false == ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS['___mysqli_ston']))) ? false : $___mysqli_res)) {
        $msg = "<h2>{$lang['poll_inp_error']}</h2>
      <a href='javascript:history.back()' title='{$lang['poll_inp_fix_it']}' style='color: green;font-weight: bold;'><span class='button is-small' style='padding:3px;'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_delete.gif' alt='{$lang['poll_inp_back']}' />{$lang['poll_inp_back']}</span></a>";
    } else {
        $msg = "<h2>{$lang['poll_inp_worked']}</h2>
      <a href='staffpanel.php?tool=polls_manager&amp;action=polls_manager' title='{$lang['poll_inp_return']}' style='color:green;font-weight:bold'><span class='button is-small' style='padding:3px;'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_tick.gif' alt='{$lang['poll_inp_success']}' />{$lang['poll_inp_success']}</span></a>";
    }
    echo stdhead($lang['poll_inp_stdhead']) . $msg . stdfoot();
}

function show_poll_form()
{
    global $site_config, $lang;
    $poll_box = poll_box($site_config['max_poll_questions'], $site_config['max_poll_choices_per_question'], 'poll_new');
    echo stdhead($lang['poll_spf_stdhead']) . $poll_box . stdfoot();
}

/**
 * @return mixed
 */
function edit_poll_form()
{
    global $site_config, $lang;
    $poll_questions = '';
    $poll_multi = '';
    $poll_choices = '';
    $poll_votes = '';
    $query = sql_query('SELECT * FROM polls WHERE pid = ' . intval($_GET['pid']));
    if (false == mysqli_num_rows($query)) {
        return $lang['poll_epf_no_poll'];
    }
    $poll_data = mysqli_fetch_assoc($query);
    $poll_answers = $poll_data['choices'] ? unserialize(stripslashes($poll_data['choices'])) : [];
    foreach ($poll_answers as $question_id => $data) {
        $poll_questions .= "\t{$question_id} : '" . str_replace("'", '&#39;', $data['question']) . "',\n";
        $data['multi'] = isset($data['multi']) ? intval($data['multi']) : 0;
        $poll_multi .= "\t{$question_id} : '" . $data['multi'] . "',\n";
        foreach ($data['choice'] as $choice_id => $text) {
            $choice = $text;
            $votes = intval($data['votes'][$choice_id]);
            $poll_choices .= "\t'{$question_id}_{$choice_id}' : '" . str_replace("'", '&#39;', $choice) . "',\n";
            $poll_votes .= "\t'{$question_id}_{$choice_id}' : '" . $votes . "',\n";
        }
    }
    $poll_questions = preg_replace("#,(\n)?$#", '\\1', $poll_questions);
    $poll_choices = preg_replace("#,(\n)?$#", '\\1', $poll_choices);
    $poll_multi = preg_replace("#,(\n)?$#", '\\1', $poll_multi);
    $poll_votes = preg_replace("#,(\n)?$#", '\\1', $poll_votes);
    $poll_question = $poll_data['poll_question'];
    $show_open = $poll_data['choices'] ? 1 : 0;
    $poll_box = poll_box($site_config['max_poll_questions'], $site_config['max_poll_choices_per_question'], 'poll_update', $poll_questions, $poll_choices, $poll_votes, $show_open, $poll_question, $poll_multi);
    echo stdhead($lang['poll_epf_stdhead']) . $poll_box . stdfoot();
}

function show_poll_archive()
{
    global $site_config, $lang;
    $HTMLOUT = '';
    $query = sql_query('SELECT * FROM polls ORDER BY start_date DESC');
    if (false == mysqli_num_rows($query)) {
        $HTMLOUT = "<h2>{$lang['poll_spa_no_polls']}</h2>
      <br>
      <a href='staffpanel.php?tool=polls_manager&amp;action=polls_manager&amp;mode=new'><span class='button is-small' style='padding:3px;' title='{$lang['poll_spa_add_title']}'><img style='vertical-align:top;' src='{$site_config['pic_baseurl']}polls/p_add.gif' alt='{$lang['poll_spa_add_alt']}' />&#160;{$lang['poll_spa_add']}</span></a>";
    } else {
        $HTMLOUT .= "<h2>{$lang['poll_spa_manage']}</h2>
      <br><br>
      <a href='staffpanel.php?tool=polls_manager&amp;action=polls_manager&amp;mode=new'><span class='button is-small' style='padding:3px;' title='{$lang['poll_spa_add']}'><img style='vertical-align:top;' src='{$site_config['pic_baseurl']}polls/p_add.gif' alt='{$lang['poll_spa_add_alt']}' />&#160;{$lang['poll_spa_add']}</span></a>
      <br><br>
      <table cellpadding='5'>
      <tr>
        <td>{$lang['poll_spa_id']}</td>
        <td>{$lang['poll_spa_question']}</td>
        <td>{$lang['poll_spa_count']}</td>
        <td>{$lang['poll_spa_date']}</td>
        <td>{$lang['poll_spa_starter']}</td>
        <td>&#160;</td>
      </tr>";
        while ($row = mysqli_fetch_assoc($query)) {
            $row['start_date'] = get_date($row['start_date'], 'DATE');
            $HTMLOUT .= '<tr>
          <td>' . (int)$row['pid'] . '</td>
          <td>' . htmlsafechars($row['poll_question']) . '</td>
          <td>' . (int)$row['votes'] . '</td>
          <td>' . htmlsafechars($row['start_date']) . "</td>
          <td><a href='userdetails.php?id=" . (int)$row['starter_id'] . "'>" . htmlsafechars($row['starter_name']) . "</a></td>
          <td><a href='staffpanel.php?tool=polls_manager&amp;action=polls_manager&amp;mode=edit&amp;pid=" . (int)$row['pid'] . "'><span class='button is-small' title='{$lang['poll_spa_edit_title']}'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_edit.gif' alt='{$lang['poll_spa_edit']}' />&#160;{$lang['poll_spa_edit']}</span></a>&#160;
          <a href='staffpanel.php?tool=polls_manager&amp;action=polls_manager&amp;mode=delete&amp;pid=" . (int)$row['pid'] . "'><span class='button is-small' title='{$lang['poll_spa_delete_title']}'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_delete.gif' alt='{$lang['poll_spa_delete']}' />&#160;{$lang['poll_spa_delete']}</span></a></td>
        </tr>";
        }
        $HTMLOUT .= '</table><br>';
    }
    echo stdhead($lang['poll_spa_stdhead']) . $HTMLOUT . stdfoot();
}

/**
 * @param string $max_poll_questions
 * @param string $max_poll_choices
 * @param string $form_type
 * @param string $poll_questions
 * @param string $poll_choices
 * @param string $poll_votes
 * @param string $show_open
 * @param string $poll_question
 * @param string $poll_multi
 *
 * @return string
 */
function poll_box($max_poll_questions = '', $max_poll_choices = '', $form_type = '', $poll_questions = '', $poll_choices = '', $poll_votes = '', $show_open = '', $poll_question = '', $poll_multi = '')
{
    global $site_config, $lang;
    $pid = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
    $form_type = ($form_type != '' ? $form_type : 'poll_update');
    $HTMLOUT = '';
    $HTMLOUT .= "
     <script src='./scripts/polls.js'></script>
     <script>
     //<![CDATA[

      var showfullonload = parseInt(\"{$show_open}\");
      
      // Questions
      var poll_questions = {{$poll_questions}};
      
      var poll_choices = {{$poll_choices}};
      
      var poll_votes = {{$poll_votes}};
      var poll_multi = {{$poll_multi}};
      
      // Setting elements
      var max_poll_questions = parseInt(\"{$max_poll_questions}\");
      var max_poll_choices   = parseInt(\"{$max_poll_choices}\");
      
      // HTML elements
      var html_add_question = \"<a href='#' title='{$lang['poll_pb_add_q']}' style='color:green;font-weight:bold' onclick='return poll_add_question()'><span class='button is-small' style='padding:3px;'><img style='vertical-align:-30%;' src='{$site_config['pic_baseurl']}polls/p_plus.gif' alt='{$lang['poll_pb_add_q']}' />{$lang['poll_pb_add_q']}</span></a>\";
      
      var html_add_choice = \"<li>&#160;<a href='#' title='{$lang['poll_pb_add_c']}' style='color:green;font-weight:bold' onclick='return poll_add_choice(\"+'\"'+'<%1>'+'\"'+\")'><span class='button is-small' style='padding:3px;'><img style='vertical-align:-30%;' src='{$site_config['pic_baseurl']}polls/p_plus.gif' alt='{$lang['poll_pb_add_c']}' />{$lang['poll_pb_add_c']}</span></a></li>\";
      
      var html_question_box = \"<input type='text' id='question_<%1>' name='question[<%1>]' size='50' class='input' value='<%2>' /> <a href='#' title='{$lang['poll_pb_remove_q']}' style='color:red;font-weight:bold' onclick='return poll_remove_question(\"+'\"'+'<%1>'+'\"'+\")'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_minus.gif' alt='{$lang['poll_pb_add_new']}' /></a><br><input class='checkbox' type='checkbox' id='multi_<%1>' name='multi[<%1>]' value='1' <%3> /><span>{$lang['poll_pb_multiple']}</span>\";
      
      var html_votes_box = \"&#160;<input type='text' id='votes_<%1>_<%2>' name='votes[<%1>_<%2>]' size='5' class='input' value='<%3>' />\";
      
      var html_choice_box = \"<li><input type='text' id='choice_<%1>_<%2>' name='choice[<%1>_<%2>]' size='35' class='input' value='<%3>' /><%4> <a href='#' title='{$lang['poll_pb_rem_choice']}' style='color:red;font-weight:bold' onclick='return poll_remove_choice(\"+'\"'+'<%1>_<%2>'+'\"'+\")'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_minus.gif' alt='{$lang['poll_pb_add_new']}' /></a></li>\";
      
      var html_choice_wrap = \"<ol><%1></ol>\";
      var html_question_wrap = \"<div><%1></div>\";
      var html_stat_wrap = \"<br><div><%1></div>\";
      
      // Lang elements
      var js_lang_confirm = \"{$lang['poll_pb_confirm']}\";
      var poll_stat_lang = \"{$lang['poll_pb_allowed']} <%1> {$lang['poll_pb_more']} <%2>  {$lang['poll_pb_choices']}\";
      
      //]]>
     </script>
     
     
     <h2>{$lang['poll_pb_editing']}</h2>
     <br>
     <a href='{$site_config['baseurl']}/staffpanel.php?tool=polls_manager&amp;action=polls_manager' title='{$lang['poll_pb_cancel']}' style='color: green;font-weight: bold;'><span class='button is-small' style='padding:3px;'><img style='vertical-align:middle;' src='{$site_config['pic_baseurl']}polls/p_delete.gif' alt='{$lang['poll_pb_cancel']}' />{$lang['poll_pb_cancel']}</span></a>
     <br><br>
     <form id='postingform' action='{$site_config['baseurl']}/staffpanel.php?tool=polls_manager&amp;action=polls_manager' method='post' name='inputform' enctype='multipart/form-data'>
     <input type='hidden' name='mode' value='{
    $form_type}' />
     <input type='hidden' name='pid' value='$pid' />

     <div style='text-align:left; width:650px; border: 1px solid black; padding:5px;'>
        <fieldset>
         <legend><strong>{$lang['poll_pb_title']}</strong></legend>
         <input type='text' size='40' class='input' name='poll_question' value='{
    $poll_question}' />
        </fieldset>

        <fieldset>
         <legend><strong>{$lang['poll_pb_content']}</strong></legend>
          <div id='poll-box-main'>

          </div>
        </fieldset>

        <fieldset>
         <legend><strong>{$lang['poll_pb_info']}</strong></legend>
          <div id='poll-box-stat'></div>
        </fieldset>
        <input type='submit' name='submit' value='{$lang['poll_pb_post']}' class='button is-small' />
     </div>

    </form>

     <script>
      poll_init_state();
     </script>";

    return $HTMLOUT;
}

/**
 * @return array
 */
function makepoll()
{
    global $site_config, $CURUSER;
    $questions = [];
    $choices_count = 0;
    $poll_total_votes = 0;
    if (isset($_POST['question']) and is_array($_POST['question']) and count($_POST['question'])) {
        foreach ($_POST['question'] as $id => $q) {
            if (!$q or !$id) {
                continue;
            }
            $questions[$id]['question'] = htmlsafechars(strip_tags($q), ENT_QUOTES);
        }
    }
    if (isset($_POST['multi']) and is_array($_POST['multi']) and count($_POST['multi'])) {
        foreach ($_POST['multi'] as $id => $q) {
            if (!$q or !$id) {
                continue;
            }
            $questions[$id]['multi'] = intval($q);
        }
    }
    if (isset($_POST['choice']) and is_array($_POST['choice']) and count($_POST['choice'])) {
        foreach ($_POST['choice'] as $mainid => $choice) {
            list($question_id, $choice_id) = explode('_', $mainid);
            $question_id = intval($question_id);
            $choice_id = intval($choice_id);
            if (!$question_id or !isset($choice_id)) {
                continue;
            }
            if (!$questions[$question_id]['question']) {
                continue;
            }
            $questions[$question_id]['choice'][$choice_id] = htmlsafechars(strip_tags($choice), ENT_QUOTES);
            $_POST['votes'] = isset($_POST['votes']) ? $_POST['votes'] : 0;
            $questions[$question_id]['votes'][$choice_id] = intval($_POST['votes'][$question_id . '_' . $choice_id]);
            $poll_total_votes += $questions[$question_id]['votes'][$choice_id];
        }
    }
    foreach ($questions as $id => $data) {
        if (!is_array($data['choice']) or !count($data['choice'])) {
            unset($questions[$id]);
        } else {
            $choices_count += intval(count($data['choice']));
        }
    }
    if (count($questions) > $site_config['max_poll_questions']) {
        die('poll_to_many');
    }
    if (count($choices_count) > ($site_config['max_poll_questions'] * $site_config['max_poll_choices_per_question'])) {
        die('poll_to_many');
    }
    if (isset($_POST['mode']) and $_POST['mode'] == 'poll_update') {
        $questions['total_votes'] = $poll_total_votes;
    }

    return $questions;
}
