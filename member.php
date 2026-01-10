<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Member Page
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
<?php
$pcpage = $_GET['pcpage'] ?? '';
$memberid = $_GET['memberid'] ?? '';

switch ($pcpage) {
    default:
        $bg1 = e($settings['tablebg1'] ?? '');
        echo "
          <tr><td colspan=\"2\" align=\"center\" bgcolor=\"{$bg1}\">
          <b>Member&uuml;bersicht</b>
          </td></tr>
        ";

        $result = $conn->query("SELECT * FROM pc_members ORDER BY nick");
        if ($result === false) {
            echo '<tr><td colspan="2" align="center" bgcolor="' . $bg1 . '"><br><b>Die Datenbank konnte nicht ausgelesen werden!</b><br><br></td></tr>';
            break;
        }

        $num = mysqli_num_rows($result);
        if ($num === 0) {
            echo "
              <tr><td colspan=\"2\" align=\"center\">
              <br>Es sind keine Member vorhanden!<br><br>
              </td></tr>
            ";
        } else {
            echo '<ul>';
            $i = 1;
            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $tablebg = ($i === 1)
                    ? e($settings['tablebg2'] ?? '')
                    : e($settings['tablebg3'] ?? '');
                $i = ($i === 1) ? 2 : 1;

                $memberId = (int)$row['id'];
                $nick = e($row['nick'] ?? '');
                $work = e($row['work'] ?? '');

                echo "
                  <tr><td width=\"60%\" bgcolor=\"{$tablebg}\">
                  <b><a href=\"member.php?pcpage=showmember&amp;memberid={$memberId}\">{$nick}</a></b>
                  </td><td width=\"40%\" bgcolor=\"{$tablebg}\">
                  {$work}
                  </td></tr>
                ";
            }
        }
        echo '</td></tr>';
        break;

    case 'showmember':
        if (empty($memberid)) {
            default_error('member.php', 'Bitte w&auml;hle einen Member aus!');
        } else {
            $stmt = $conn->prepare("SELECT * FROM pc_members WHERE id = ?");
            $memberIdInt = (int)$memberid;
            $stmt->bind_param('i', $memberIdInt);
            $stmt->execute();
            $result = $stmt->get_result();
            $num = mysqli_num_rows($result);

            if ($num === 1) {
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);

                // Safe values
                $nick = e($row['nick'] ?? '');
                $email = e($row['email'] ?? '');
                $work = e($row['work'] ?? '');
                $icqRaw = $row['icq'] ?? '';
                $homepageRaw = $row['homepage'] ?? '';
                $realnameRaw = $row['realname'] ?? '';
                $ageRaw = $row['age'] ?? '';
                $infoRaw = $row['info'] ?? '';
                $hardwareRaw = $row['hardware'] ?? '';
                $picRaw = $row['pic'] ?? '';

                $icq = empty($icqRaw)
                    ? 'N/A'
                    : '<a href="https://web.icq.com/people/' . e($icqRaw) . '">' . e($icqRaw) . '</a>';

                $homepage = empty($homepageRaw)
                    ? 'Keine Homepage'
                    : '<a href="' . e($homepageRaw) . '" target="_blank" rel="noopener noreferrer">' . e($homepageRaw) . '</a>';

                $realname = empty($realnameRaw) ? 'N/A' : e($realnameRaw);
                $age = empty($ageRaw) ? 'N/A' : e($ageRaw) . ' Jahre';
                $infos = empty($infoRaw) ? 'Keine pers&ouml;nlichen Informationen' : nl2br(e($infoRaw));
                $hardware = empty($hardwareRaw) ? 'Keine Hardwareinformationen' : nl2br(e($hardwareRaw));

                if (!empty($picRaw)) {
                    $safePic = e($picRaw);
                    $pic = "<a href=\"showpic.php?path={$safePic}\"><img src=\"{$safePic}\" border=\"0\" width=\"145\" alt=\"{$nick}\"></a>";
                } else {
                    $pic = 'Kein Bild vorhanden';
                }

                $bg1 = e($settings['tablebg1'] ?? '');
                $bg2 = e($settings['tablebg2'] ?? '');
                $bg3 = e($settings['tablebg3'] ?? '');

                echo "
                  <tr><td colspan=\"3\" align=\"center\" bgcolor=\"{$bg1}\">
                  <b>{$nick}s Details</b>
                  </td></tr>
                  <tr><td bgcolor=\"{$bg2}\" width=\"125\">
                  <b>E-Mail</b>
                  </td><td bgcolor=\"{$bg2}\">
                  <a href=\"mailto:{$email}\">{$email}</a>
                  </td><td rowspan=\"8\" bgcolor=\"{$bg1}\" width=\"150\" align=\"center\">
                  {$pic}
                  </td></tr>
                  <tr><td bgcolor=\"{$bg3}\">
                  <b>Aufgabe</b>
                  </td><td bgcolor=\"{$bg3}\">
                  {$work}
                  </td></tr>
                  <tr><td bgcolor=\"{$bg2}\">
                  <b>ICQ</b>
                  </td><td bgcolor=\"{$bg2}\">
                  {$icq}
                  </td></tr>
                  <tr><td bgcolor=\"{$bg3}\">
                  <b>Homepage</b>
                  </td><td bgcolor=\"{$bg3}\">
                  {$homepage}
                  </td></tr>
                  <tr><td bgcolor=\"{$bg2}\">
                  <b>Realname</b>
                  </td><td bgcolor=\"{$bg2}\">
                  {$realname}
                  </td></tr>
                  <tr><td bgcolor=\"{$bg3}\">
                  <b>Alter</b>
                  </td><td bgcolor=\"{$bg3}\">
                  {$age}
                  </td></tr>
                  <tr><td bgcolor=\"{$bg2}\" valign=\"top\">
                  <b>Pers&ouml;nliche Infos</b>
                  </td><td bgcolor=\"{$bg2}\">
                  {$infos}
                  </td></tr>
                  <tr><td bgcolor=\"{$bg3}\" valign=\"top\">
                  <b>Hardware</b>
                  </td><td bgcolor=\"{$bg3}\">
                  {$hardware}
                  </td></tr>
                  <tr><td colspan=\"3\" align=\"center\" bgcolor=\"{$bg1}\">
                  <a href=\"member.php\">Zur&uuml;ck zur Member&uuml;bersicht</a>
                  </td></tr>
                ";
            } else {
                default_error('member.php', 'Bitte w&auml;hle einen existierenden Member aus!');
            }
            $stmt->close();
        }
        break;
}
?>
</table>
</center>
<br>
<center>
<small><a href="https://www.powerscripts.org" target="_blank" rel="noopener noreferrer">PowerClan</a> &copy; Copyright 2001-2025 by <a href="mailto:info@powerscripts.org?subject=PowerClan Copyright">PowerScripts</a></small>
</center>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
