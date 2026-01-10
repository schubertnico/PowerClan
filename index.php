<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Main Index Page
 *
 * @copyright 2001-2025 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

?>
<!--HEADER FILE-->
<?php include __DIR__ . '/header.inc.php'; ?>
<!--MAIN PAGE-->

<center>
<table border="0" cellpadding="3" cellspacing="2" width="100%">
  <tr><td width="50%" bgcolor="<?php echo e($settings['tablebg1'] ?? ''); ?>">
  <b>latest news</b>
  </td><td width="50%" bgcolor="<?php echo e($settings['tablebg1'] ?? ''); ?>">
  <b>latest wars</b>
  </td></tr>
  <tr><td valign="top" bgcolor="<?php echo e($settings['tablebg2'] ?? ''); ?>">
<?php
$newsLimit = (int)($settings['newslimit'] ?? 5);
$stmt = $conn->prepare("SELECT * FROM pc_news ORDER BY id DESC LIMIT ?");
$stmt->bind_param('i', $newsLimit);
$stmt->execute();
$result = $stmt->get_result();
$num = mysqli_num_rows($result);

if ($num !== 0) {
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $date = date('d.m.Y', (int)$row['time']);
        $newsId = (int)$row['id'];
        $title = e($row['title'] ?? '');
        echo "<small>{$date}</small> <a href=\"#news{$newsId}\">{$title}</a><br>\n";
    }
}
$stmt->close();
?>
  </td><td valign="top" bgcolor="<?php echo e($settings['tablebg2'] ?? ''); ?>">
<?php
$warLimit = (int)($settings['warlimit'] ?? 5);
$stmt = $conn->prepare("SELECT * FROM pc_wars WHERE res1 != '' AND res2 != '' ORDER BY time DESC LIMIT ?");
$stmt->bind_param('i', $warLimit);
$stmt->execute();
$result = $stmt->get_result();
$num = mysqli_num_rows($result);

if ($num !== 0) {
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $date = date('d.m.Y', (int)$row['time']);
        $allres = ['left' => 0, 'right' => 0];

        if (!empty($row['map1']) && !empty($row['res1'])) {
            $res = explode(':', (string) $row['res1']);
            $allres['left'] += (int)($res[0] ?? 0);
            $allres['right'] += (int)($res[1] ?? 0);
        }
        if (!empty($row['map2']) && !empty($row['res2'])) {
            $res = explode(':', (string) $row['res2']);
            $allres['left'] += (int)($res[0] ?? 0);
            $allres['right'] += (int)($res[1] ?? 0);
        }
        if (!empty($row['map3']) && !empty($row['res3'])) {
            $res = explode(':', (string) $row['res3']);
            $allres['left'] += (int)($res[0] ?? 0);
            $allres['right'] += (int)($res[1] ?? 0);
        }

        if ($allres['left'] > $allres['right']) {
            $style = e($settings['clrwon'] ?? '#00FF00');
        } elseif ($allres['left'] === $allres['right']) {
            $style = e($settings['clrdraw'] ?? '#FFFF00');
        } else {
            $style = e($settings['clrlost'] ?? '#FF0000');
        }

        $warId = (int)$row['id'];
        $clanTag = e($settings['clantag'] ?? '');
        $enemyTag = e($row['enemy_tag'] ?? '');
        echo "<small>{$date}</small> <a href=\"wars.php#war{$warId}\" style=\"color: {$style}\">{$clanTag} vs. {$enemyTag}</a><br>\n";
    }
} else {
    echo 'Keine Wars vorhanden';
}
$stmt->close();
?>
  </td></tr>
</table>
<table border="0" cellpadding="3" cellspacing="2" width="100%">
<?php
$stmt = $conn->prepare("SELECT * FROM pc_news ORDER BY id DESC LIMIT ?");
$stmt->bind_param('i', $newsLimit);
$stmt->execute();
$result = $stmt->get_result();
$num = mysqli_num_rows($result);

if ($num !== 0) {
    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
        $date = date('d.m.Y', (int)$row['time']);
        $text = news_replace($row['text'] ?? '');
        $newsId = (int)$row['id'];
        $title = e($row['title'] ?? '');
        $nick = e($row['nick'] ?? '');
        $email = e($row['email'] ?? '');
        $bg1 = e($settings['tablebg1'] ?? '');
        $bg2 = e($settings['tablebg2'] ?? '');
        $bg3 = e($settings['tablebg3'] ?? '');

        echo "
          <tr><td height=\"10\">
          </td></tr>
          <tr><td bgcolor=\"{$bg1}\" colspan=\"2\">
          <a name=\"#news{$newsId}\"></a><b>{$title}</b>
          </td></tr>
          <tr><td bgcolor=\"{$bg2}\" valign=\"top\">
          {$text}
          </td><td bgcolor=\"{$bg3}\" valign=\"top\" width=\"100\" align=\"right\">
          {$date}<br>
          <br>
          <a href=\"mailto:{$email}\">{$nick}</a><br>
          <br>
          </td></tr>
        ";
    }
}
$stmt->close();
?>
</table>
</center>
<br>
<center>
<small><a href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerClan</a> &copy; Copyright 2001-2025 by <a href="mailto:info@powerscripts.org?subject=PowerClan Copyright">PowerScripts</a></small>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
