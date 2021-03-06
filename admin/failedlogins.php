<?php
require_once INCL_DIR . 'user_functions.php';
require_once INCL_DIR . 'html_functions.php';
require_once INCL_DIR . 'pager_functions.php';
require_once CLASS_DIR . 'class_check.php';
$class = get_access(basename($_SERVER['REQUEST_URI']));
class_check($class);
global $site_config, $lang;

$HTMLOUT = '';
$lang = array_merge($lang, load_language('failedlogins'));
$mode = (isset($_GET['mode']) ? $_GET['mode'] : '');
$id = isset($_GET['id']) ? (int)$_GET['id'] : '';
/**
 * @param $id
 *
 * @return bool
 */
function validate($id)
{
    global $lang;
    if (!is_valid_id($id)) {
        stderr($lang['failed_sorry'], "{$lang['failed_bad_id']}");
    }
    return true;
}

if ($mode == 'ban') {
    validate($id);
    sql_query("UPDATE failedlogins SET banned = 'yes' WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
    header('Refresh: 2; url=' . $site_config['baseurl'] . '/staffpanel.php?tool=failedlogins');
    stderr($lang['failed_success'], "{$lang['failed_message_ban']}");
    die();
}
if ($mode == 'removeban') {
    validate($id);
    sql_query("UPDATE failedlogins SET banned = 'no' WHERE id = " . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
    header('Refresh: 2; url=' . $site_config['baseurl'] . '/staffpanel.php?tool=failedlogins');
    stderr($lang['failed_success'], "{$lang['failed_message_unban']}");
    die();
}
if ($mode == 'delete') {
    validate($id);
    sql_query('DELETE FROM failedlogins WHERE id=' . sqlesc($id)) or sqlerr(__FILE__, __LINE__);
    header('Refresh: 2; url=' . $site_config['baseurl'] . '/staffpanel.php?tool=failedlogins');
    stderr($lang['failed_success'], "{$lang['failed_message_deleted']}");
    die();
}
//==End
//==Main output
$where = '';
$search = isset($_POST['search']) ? strip_tags($_POST['search']) : '';
if (isset($_GET['search'])) {
    $search = strip_tags($_GET['search']);
}
if (!$search) {
    $where = 'WHERE attempts LIKE ' . sqlesc("%$search%") . '';
} else {
    $where = 'WHERE attempts LIKE' . sqlesc("%$search%") . '';
}
$res = sql_query("SELECT COUNT(id) FROM failedlogins $where") or sqlerr(__FILE__, __LINE__);
$row = mysqli_fetch_row($res);
$count = $row[0];
$perpage = 15;
$pager = pager($perpage, $count, 'staffpanel.php?tool=failedlogins&amp;action=failedlogins&amp;' . (!empty($search) ? "search=$search&amp;" : '') . '');
if (!$where) {
    stderr($lang['failed_main_nofail'], $lang['failed_main_nofail_msg']);
}
$HTMLOUT = '';
$HTMLOUT .= "<table width='115'>\n
             <tr>
             <td class='tabletitle'>{$lang['failed_main_search']}</td>\n
             </tr>
             <tr>
             <td class='table'>\n
             <form method='post' action='staffpanel.php?tool=failedlogins&amp;action=failedlogins'>\n
             <input type='text' name='search' size='40' value='' />\n
             <input type='submit' value='{$lang['failed_main_search_btn']}' style='height: 20px;' />\n
             </form></td></tr></table>";
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagertop'];
}
$HTMLOUT .= "<table  width='80%'>\n";
$res = sql_query("SELECT f.*,u.id as uid, u.username FROM failedlogins as f LEFT JOIN users as u ON u.ip = f.ip $where ORDER BY f.added DESC " . $pager['limit']) or sqlerr(__FILE__, __LINE__);
if (mysqli_num_rows($res) == 0) {
    $HTMLOUT .= "<tr><td colspan='2'><b>{$lang['failed_message_nothing']}</b></td></tr>\n";
} else {
    $HTMLOUT .= "<tr><td class='colhead'>{$lang['failed_main_id']}</td><td class='colhead'>{$lang['failed_main_ip']}</td><td class='colhead'>{$lang['failed_main_added']}</td>" . "<td class='colhead'>{$lang['failed_main_attempts']}</td><td class='colhead'>{$lang['failed_main_status']}</td></tr>\n";
    while ($arr = mysqli_fetch_assoc($res)) {
        $HTMLOUT .= "<tr><td><b>" . (int)$arr['id'] . "</b></td>
  <td><b>" . htmlsafechars($arr['ip']) . ' ' . ((int)$arr['uid'] ? "<a href='{$site_config['baseurl']}/userdetails.php?id=" . (int)$arr['uid'] . "'>" : '') . ' ' . (htmlsafechars($arr['username']) ? '(' . htmlsafechars($arr['username']) . ')</a>' : '') . "</b></td>
  <td><b>" . get_date($arr['added'], '', 1, 0) . "</b></td>
  <td><b>" . (int)$arr['attempts'] . "</b></td>
  <td>
  " . ($arr['banned'] == 'yes' ? "<span class='has-text-danger'><b>{$lang['failed_main_banned']}</b></span> 
  <a href='staffpanel.php?tool=failedlogins&amp;action=failedlogins&amp;mode=removeban&amp;id=" . (int)$arr['id'] . "'> 
  <span style='color: green;'>[<b>{$lang['failed_main_remban']}</b>]</font></a>" : "<font color='green;'><b>{$lang['failed_main_noban']}</b></span> 
  <a href='staffpanel.php?tool=failedlogins&amp;action=failedlogins&amp;mode=ban&amp;id=" . (int)$arr['id'] . "'><span class='has-text-danger'>[<b>{$lang['failed_main_ban']}</b>]</span></a>") . "  
  
  <a onclick=\"return confirm('{$lang['failed_main_delmessage']}');\" href='staffpanel.php?tool=failedlogins&amp;action=failedlogins&amp;mode=delete&amp;id=" . (int)$arr['id'] . "'>[<b>{$lang['failed_main_delete']}</b>]</a></td></tr>\n";
    }
}
$HTMLOUT .= "</table>\n";
if ($count > $perpage) {
    $HTMLOUT .= $pager['pagerbottom'];
}
echo stdhead($lang['failed_main_logins']) . $HTMLOUT . stdfoot();
