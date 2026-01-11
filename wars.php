<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Wars Page
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
<table border="0" cellpadding="2" cellspacing="2" width="100%">
<?php
$pcpage = $_GET['pcpage'] ?? '';

switch ($pcpage) {
    default:
        getwarstats();

        $bg1 = e($settings['tablebg1'] ?? '');
        echo "
          <tr><td colspan=\"9\" align=\"center\" bgcolor=\"{$bg1}\">
          <b>War&uuml;bersicht</b>
          </td></tr>
        ";

        $result = $conn->query('SELECT * FROM pc_wars ORDER BY time DESC');
        if ($result === false) {
            break;
        }

        $num = mysqli_num_rows($result);
        if ($num === 0) {
            $bg2 = e($settings['tablebg2'] ?? '');
            echo "
              <tr><td colspan=\"9\" align=\"center\" bgcolor=\"{$bg2}\">
              Keine Wars vorhanden
              </td></tr>
            ";
        } else {
            echo "
              <tr><td bgcolor=\"{$bg1}\" width=\"20\" align=\"center\">
              <b>#</b>
              </td><td bgcolor=\"{$bg1}\" width=\"60\" align=\"center\">
              <b>Gegner</b>
              </td><td bgcolor=\"{$bg1}\" width=\"80\" align=\"center\">
              <b>Termin</b>
              </td><td bgcolor=\"{$bg1}\" width=\"60\" align=\"center\">
              <b>Liga</b>
              </td><td bgcolor=\"{$bg1}\" width=\"80\" align=\"center\">
              <b>Map 1</b>
              </td><td bgcolor=\"{$bg1}\" width=\"80\" align=\"center\">
              <b>Map 2</b>
              </td><td bgcolor=\"{$bg1}\" width=\"80\" align=\"center\">
              <b>Map 3</b>
              </td><td bgcolor=\"{$bg1}\" width=\"80\" align=\"center\">
              <b>Ergebnis</b>
              </td><td bgcolor=\"{$bg1}\" width=\"*\" align=\"center\">
              <b>Bericht</b>
              </td></tr>
            ";

            $warnumber = $num;
            $bgnum = 0;

            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                $bgcolor = ($bgnum === 0)
                    ? e($settings['tablebg2'] ?? '')
                    : e($settings['tablebg3'] ?? '');
                $bgnum = ($bgnum === 0) ? 1 : 0;

                $endres = [0, 0];
                $warId = (int) $row['id'];
                $date = date('d.m.Y', (int) $row['time']);
                $time = date('H:i', (int) $row['time']);
                $enemyTag = e($row['enemy_tag'] ?? '');
                $homepage = $row['homepage'] ?? '';
                $league = e($row['league'] ?? '');

                echo "
                  <tr><td bgcolor=\"{$bgcolor}\" align=\"center\">
                  <a name=\"#war{$warId}\"></a>{$warnumber}
                  </td><td bgcolor=\"{$bgcolor}\" align=\"center\">
                ";

                if (!empty($homepage)) {
                    echo '<a href="' . e($homepage) . '" target="_blank" rel="noopener noreferrer">' . $enemyTag . '</a>';
                } else {
                    echo $enemyTag;
                }

                echo "
                  </td><td bgcolor=\"{$bgcolor}\" align=\"center\">
                  {$date}<br>
                  {$time}
                  </td><td bgcolor=\"{$bgcolor}\" align=\"center\">
                  {$league}
                  </td><td bgcolor=\"{$bgcolor}\" align=\"center\">
                ";

                // Map 1
                $map1 = e($row['map1'] ?? '');
                $screen1 = $row['screen1'] ?? '';
                if (!empty($screen1)) {
                    echo '<a href="showpic.php?path=images/wars/' . e($screen1) . '">' . $map1 . '</a><br>';
                } else {
                    echo $map1 . '<br>';
                }
                if (!empty($row['res1'])) {
                    $res = explode(':', (string) $row['res1']);
                    $r0 = (int) ($res[0] ?? 0);
                    $r1 = (int) ($res[1] ?? 0);
                    if ($r0 > $r1) {
                        $style = e($settings['clrwon'] ?? '#00FF00');
                    } elseif ($r0 === $r1) {
                        $style = e($settings['clrdraw'] ?? '#FFFF00');
                    } else {
                        $style = e($settings['clrlost'] ?? '#FF0000');
                    }
                    $endres[0] += $r0;
                    $endres[1] += $r1;
                    echo "<b style=\"font-weight: normal; color: {$style}\">{$r0}:{$r1}</b>";
                }

                echo "
                  </td><td bgcolor=\"{$bgcolor}\" align=\"center\">
                ";

                // Map 2
                $map2 = e($row['map2'] ?? '');
                $screen2 = $row['screen2'] ?? '';
                if (!empty($screen2)) {
                    echo '<a href="showpic.php?path=images/wars/' . e($screen2) . '">' . $map2 . '</a><br>';
                } else {
                    echo $map2 . '<br>';
                }
                if (!empty($row['res2'])) {
                    $res = explode(':', (string) $row['res2']);
                    $r0 = (int) ($res[0] ?? 0);
                    $r1 = (int) ($res[1] ?? 0);
                    if ($r0 > $r1) {
                        $style = e($settings['clrwon'] ?? '#00FF00');
                    } elseif ($r0 === $r1) {
                        $style = e($settings['clrdraw'] ?? '#FFFF00');
                    } else {
                        $style = e($settings['clrlost'] ?? '#FF0000');
                    }
                    $endres[0] += $r0;
                    $endres[1] += $r1;
                    echo "<b style=\"font-weight: normal; color: {$style}\">{$r0}:{$r1}</b>";
                }

                echo "
                  </td><td bgcolor=\"{$bgcolor}\" align=\"center\">
                ";

                // Map 3
                $map3 = e($row['map3'] ?? '');
                $screen3 = $row['screen3'] ?? '';
                if (!empty($screen3)) {
                    echo '<a href="showpic.php?path=images/wars/' . e($screen3) . '">' . $map3 . '</a><br>';
                } else {
                    echo $map3 . '<br>';
                }
                if (!empty($row['res3'])) {
                    $res = explode(':', (string) $row['res3']);
                    $r0 = (int) ($res[0] ?? 0);
                    $r1 = (int) ($res[1] ?? 0);
                    if ($r0 > $r1) {
                        $style = e($settings['clrwon'] ?? '#00FF00');
                    } elseif ($r0 === $r1) {
                        $style = e($settings['clrdraw'] ?? '#FFFF00');
                    } else {
                        $style = e($settings['clrlost'] ?? '#FF0000');
                    }
                    $endres[0] += $r0;
                    $endres[1] += $r1;
                    echo "<b style=\"font-weight: normal; color: {$style}\">{$r0}:{$r1}</b>";
                }

                echo "
                  </td><td bgcolor=\"{$bgcolor}\" align=\"center\">
                ";

                // Total result
                if ($endres[0] >= 0 && $endres[1] >= 0) {
                    if ($endres[0] > $endres[1]) {
                        $style = e($settings['clrwon'] ?? '#00FF00');
                    } elseif ($endres[0] === $endres[1]) {
                        $style = e($settings['clrdraw'] ?? '#FFFF00');
                    } else {
                        $style = e($settings['clrlost'] ?? '#FF0000');
                    }
                    echo "<b style=\"font-weight: normal; color: {$style}\">{$endres[0]}:{$endres[1]}</b>";
                }

                echo "
                  </td><td bgcolor=\"{$bgcolor}\" align=\"center\">
                ";

                if (!empty($row['report'])) {
                    echo '<a href="wars.php?pcpage=showreport&amp;warid=' . $warId . '"><img src="images/report.gif" border="0" alt="Bericht lesen"></a>';
                }

                echo '
                  </td></tr>
                ';

                $warnumber--;
            }
        }
        break;

    case 'showreport':
        $warid = $_GET['warid'] ?? '';
        if (empty($warid)) {
            default_error('wars.php', 'Du musst einen War ausw&auml;hlen!');
        } else {
            $stmt = $conn->prepare('SELECT * FROM pc_wars WHERE id = ?');
            $warIdInt = (int) $warid;
            $stmt->bind_param('i', $warIdInt);
            $stmt->execute();
            $result = $stmt->get_result();
            $num = mysqli_num_rows($result);

            if ($num === 1) {
                $row = mysqli_fetch_array($result, MYSQLI_ASSOC);
                if (!empty($row['report'])) {
                    $report = news_replace($row['report']);
                    $clanname = e($settings['clanname'] ?? '');
                    $enemy = e($row['enemy'] ?? '');
                    $bg1 = e($settings['tablebg1'] ?? '');
                    $bg2 = e($settings['tablebg2'] ?? '');

                    echo "
                      <tr><td align=\"center\" bgcolor=\"{$bg1}\">
                      <b>{$clanname} vs. {$enemy}</b>
                      </td></tr>
                      <tr><td bgcolor=\"{$bg2}\">
                      {$report}
                      </td></tr>
                      <tr><td bgcolor=\"{$bg1}\" align=\"center\">
                      <a href=\"wars.php\">Zur&uuml;ck zum War&uuml;berblick</a>
                      </td></tr>
                    ";
                } else {
                    default_error('wars.php', 'Zum gew&auml;hlten War wurde noch kein Bericht geschrieben!');
                }
            } else {
                default_error('wars.php', 'Der gew&auml;hlte War existiert nicht!');
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
