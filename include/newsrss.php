<?php

function foxnews_shout()
{
    global $INSTALLER09, $mc1;
    if ($INSTALLER09['autoshout_on'] == 1) {
        require_once INCL_DIR . 'user_functions.php';
        if (($xml = $mc1->get_value('foxnewsrss_')) === false) {
            $xml = file_get_contents('http://feeds.foxnews.com/foxnews/tech');
            $mc1->cache_value('foxnewsrss_', $xml, 300);
        }
        $doc = new DOMDocument();
        @$doc->loadXML($xml);
        $items = $doc->getElementsByTagName('item');
        $pubs = [];
        foreach ($items as $item) {
            $title       = empty($item->getElementsByTagName('title')      ->item(0)->nodeValue) ? '' : $item->getElementsByTagName('title')      ->item(0)->nodeValue;
            $link        = empty($item->getElementsByTagName('link')       ->item(0)->nodeValue) ? '' : $item->getElementsByTagName('link')       ->item(0)->nodeValue;
            preg_match('/\d{4}\/\d{2}\/\d{2}\/(.*)/', $link, $match);
            $short_link = !empty($match[1]) ? $match[1] : $link;
            $pubs[] = [
                        'title' => replace_unicode_strings($title),
                        'link' => replace_unicode_strings($link),
                        'short_link' => replace_unicode_strings($short_link),
            ];
        }
        $pubs = array_reverse($pubs);
        foreach ($pubs as $pub) {
            $title = sqlesc($pub['title']);
            $short_link = sqlesc($pub['short_link']);
            sql_query("INSERT INTO newsrss (link)
                        SELECT $short_link
                        FROM DUAL
                        WHERE NOT EXISTS(
                            SELECT 1
                            FROM newsrss
                            WHERE link = $short_link
                        )
                        LIMIT 1") or sqlerr(__FILE__, __LINE__);
            $newid = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS['___mysqli_ston']))) ? false : $___mysqli_res);
            if ($newid) {
                $msg = "[color=yellow]In The News:[/color] [url={$pub['link']}]{$pub['title']}[/url]";
                autoshout($msg);
                return false;
            }
        }
    }
    return true;
}

function tfreak_shout()
{
    global $INSTALLER09, $mc1;
    if ($INSTALLER09['autoshout_on'] == 1) {
        require_once INCL_DIR . 'user_functions.php';
        if (($xml = $mc1->get_value('tfreaknewsrss_')) === false) {
            $xml = file_get_contents('http://feed.torrentfreak.com/Torrentfreak/');
            $mc1->cache_value('tfreaknewsrss_', $xml, 300);
        }
        $doc = new DOMDocument();
        @$doc->loadXML($xml);
        $items = $doc->getElementsByTagName('item');
        $pubs = [];
        foreach ($items as $item) {
            $title = empty($item->getElementsByTagName('title')->item(0)->nodeValue) ? '' : $item->getElementsByTagName('title')->item(0)->nodeValue;
            $link  = empty($item->getElementsByTagName('link') ->item(0)->nodeValue) ? '' : $item->getElementsByTagName('link') ->item(0)->nodeValue;
            $pubs[] = [
                        'title' => replace_unicode_strings($title),
                        'link' => replace_unicode_strings($link)
            ];
        }
        $pubs = array_reverse($pubs);
        foreach ($pubs as $pub) {
            $title = sqlesc($pub['title']);
            $link = sqlesc($pub['link']);
            sql_query("INSERT INTO newsrss (link)
                        SELECT $link
                        FROM DUAL
                        WHERE NOT EXISTS(
                            SELECT 1
                            FROM newsrss
                            WHERE link = $link
                        )
                        LIMIT 1") or sqlerr(__FILE__, __LINE__);
            $newid = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS['___mysqli_ston']))) ? false : $___mysqli_res);
            if ($newid) {
                $msg = "[color=yellow]In The News:[/color] [url={$pub['link']}]{$pub['title']}[/url]";
                autoshout($msg);
                return false;
            }
        }
    }
    return true;
}

function github_shout()
{
    global $INSTALLER09, $mc1;
    if ($INSTALLER09['autoshout_on'] == 1) {
        require_once INCL_DIR . 'user_functions.php';
        if (($rss = $mc1->get_value('githubcommitrss_')) === false) {
            $rss = file_get_contents('https://github.com/darkalchemy/P-239-V1/commits/master.atom');
            $mc1->cache_value('githubcommitrss_', $rss, 300);
        }
        $xml = simplexml_load_string($rss);
        $items = $xml->entry;
        $pubs = [];
        foreach ($items as $item) {
            $devices = json_decode(json_encode($item), true);
            preg_match('/Commit\/(.*)/', $devices['id'], $match);
            $commit = trim($match[1]);
            $title = trim($devices['title']);
            $link = trim($devices['link']["@attributes"]['href']);
            $author = trim($devices['author']['name']);

            $pubs[] = [
                        'title' => replace_unicode_strings($title),
                        'link' => replace_unicode_strings($link),
                        'author' => replace_unicode_strings($author),
                        'commit' => replace_unicode_strings($commit)
            ];
        }
        $pubs = array_reverse($pubs);
        foreach ($pubs as $pub) {
            $title = sqlesc($pub['title']);
            $link = sqlesc($pub['link']);
            sql_query("INSERT INTO newsrss (link)
                        SELECT $link
                        FROM DUAL
                        WHERE NOT EXISTS(
                            SELECT 1
                            FROM newsrss
                            WHERE link = $link
                        )
                        LIMIT 1") or sqlerr(__FILE__, __LINE__);
            $newid = ((is_null($___mysqli_res = mysqli_insert_id($GLOBALS['___mysqli_ston']))) ? false : $___mysqli_res);
            if ($newid) {
                $msg = "[color=yellow]Git Commit:[/color] [url={$pub['link']}]{$pub['title']}[/url] => {$pub['commit']}";
                autoshout($msg, 0, 86400);
                return false;
            }
        }
    }
    return true;
}
