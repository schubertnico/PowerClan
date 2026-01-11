<?php
/************************************************************************/
/* PowerClan is a PHP and mySQL based clanportal - www.powerscripts.org */
/* Copyright (C) 2001-2023 PowerScripts                                 */
/*                                                                      */
/* This program is free software; you can redistribute it and/or modify */
/* it under the terms of the GNU General Public License as published by */
/* the Free Software Foundation; either version 2 of the License, or    */
/* (at your option) any later version.                                  */
/*                                                                      */
/* This program is distributed in the hope that it will be useful,      */
/* but WITHOUT ANY WARRANTY; without even the implied warranty of       */
/* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the        */
/* GNU General Public License for more details.                         */
/*                                                                      */
/* You should have received a copy of the GNU General Public License    */
/* along with this program; if not, write to the Free Software          */
/* Foundation, Inc., 59 Temple Place, Suite 330, Boston,                */
/* MA  02111-1307  USA                                                  */
/************************************************************************/
?>
<html>
<head>
<title>PowerClan Installation</title>
<meta name="author" content="Stefan 'BFG' Kraemer">
</head>
<link rel="stylesheet" href="powerclan.css" type="text/css">
<body text="#000000" bgcolor="#FFFFFF" link="#000080" alink="#000080" vlink="#000080">

<center>
<table border="0" cellpadding="0" cellspacing="0" width="95%">
<tr><td bgcolor="#000080" width="100%">
  <a name="#top"></a>
  <table border="0" width="100%" cellpadding="2" cellspacing="1">
  <tr><td bgcolor="#E0E0E0" width="125" valign="top">
  <br>
  <a href="<?php echo $_SERVER['PHP_SELF'] . '?type=install'; ?>">Installation</a><br>
  <br>
  <a href="<?php echo $_SERVER['PHP_SELF'] . '?type=update&version=1.0'; ?>">Update von 1.0</a><br>
  <br>
  </td><td bgcolor="#F0F0F0" valign="top">
  <br>
<?php
  $type = $_GET['type'] ?? '';
$page = $_GET['page'] ?? '';
$mysql = $_POST['mysql'] ?? [];

$configfile = $_POST['configfile'] ?? '';
if ($configfile == '') {
    $configfile = $_GET['configfile'] ?? '';
}
$mysqltables = $_POST['mysqltables'] ?? '';
if ($mysqltables == '') {
    $mysqltables = $_GET['mysqltables'] ?? '';
}

$nickname = $_POST['nickname'] ?? '';
$email = $_POST['email'] ?? '';

function generate_password(): string
{

    $now = time();
    mt_srand($now);
    $pwarray = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

    $pwacount = count($pwarray);
    $password = '';
    $i = 0;
    while ($letter = random_int(0, $pwacount - 1) and $i !== 8) {
        $password .= $pwarray[$letter];
        $i++;
    }

    return $password;
}
switch ($type) {
    default:
        echo '
        <h2>PowerClan Installation</h2>
        Beachte bei der Installation von PowerClan, dass alle Einstellungen einer vorher installierten
        Version gel&ouml;scht werden!<br>
        <br>
      ';
        break;
    case 'install':
        switch ($page) {
            default:
                echo '
            W&auml;hle die Stufen der automatischen Installation. Wenn eine Stufe nicht gew&auml;hlt ist, so muss
            diese manuell durchgef&uuml;hrt werden. An der entsprechenden Stelle findest Du eine Beschreibung
            des Vorgangs!
            <form action="' . $_SERVER['PHP_SELF'] . '?type=install&page=1" method="post">
            <table border="0" cellpadding="3" cellspacing="0">
            <tr><td>
            <b>mySQL Tabellen automatisch anlegen</b>
            </td><td>
            <input name="mysqltables" type="checkbox" value="YES" checked>
            </td></tr>
            <tr><td>
            <b>Konfigurationsdatei automatisch anlegen</b>
            </td><td>
            <input name="configfile" type="checkbox" value="YES" checked>
            </td></tr>
            </table>
            <div align="right"><input type="submit" value="Weiter >>"></div>
            </form>
          ';
                break;
            case '1':
                if ($configfile == 'YES') {
                    echo '
              <form action="' . $_SERVER['PHP_SELF'] . "?type=install&page=2&configfile=$configfile&mysqltables=$mysqltables\" method=\"post\">
              <table border=\"0\" cellpadding=\"3\" cellspacing=\"0\">
              <tr><td>
              <b>mySQL Server</b>
              </td><td>
              <input name=\"mysql[host]\" size=\"50\" maxlength=\"200\">
              </td></tr>
              <tr><td>
              <b>mySQL Datenbank</b>
              </td><td>
              <input name=\"mysql[database]\" size=\"50\" maxlength=\"200\">
              </td></tr>
              <tr><td>
              <b>mySQL User</b>
              </td><td>
              <input name=\"mysql[user]\" size=\"50\" maxlength=\"200\">
              </td></tr>
              <tr><td>
              <b>mySQL Passwort</b>
              </td><td>
              <input name=\"mysql[password]\" size=\"50\" maxlength=\"200\" type=\"password\"> (optional)
              </td></tr>
              <tr><td>
              <b>mySQL Port</b>
              </td><td>
              <input name=\"mysql[port]\" size=\"5\" maxlength=\"5\" value=\"3306\"> (Standardport = 3306)
              </td></tr>
              </table>
              <div align=\"right\"><input type=\"submit\" value=\"Weiter >>\"></div>
              </form>
            ";
                } else {
                    echo "
              Du hast Dich dazu entschieden die Konfigurationsdatei manuell anzulegen.<br>
              Bitte halte Dich genau an die Anleitung, damit alles funktionieren kann.<br>
              <br>
              Erstelle zuerst eine \"config.inc.php\" oder &ouml;ffne die vorhandene mit einem Text- oder HTML
              Editor.<br>
              <br>
              In der \"config.inc.php\" sollte folgendes stehen:
              <blockquote>
                &lt;?PHP<br>
                &nbsp;&nbsp;\$mysql['host'] = \"\";<br>
                &nbsp;&nbsp;\$mysql['user'] = \"\";<br>
                &nbsp;&nbsp;\$mysql['password'] = \"\";<br>
                &nbsp;&nbsp;\$mysql['database'] = \"\";<br>
                &nbsp;&nbsp;\$mysql['port'] = \"3306\";<br>
                  \$settings['tablebg1'] = \"#000000\";
                  \$settings['footer']=\"\";
                  \$version=2.00;
                ?&gt;<br>
              </blockquote>
              F&uuml;ge nun zwischen die 2 \"s die entsprechenden Daten ein. Also zum Beispiel bei \$mysql['host']
              Deinen mySQL Server, oder bei \$mysql['password'] das Passwort f&uuml;r Deinen mySQL Benutzer.<br>
              Der Port <b>3306</b> ist der Standard mySQL Port, sollte Dein Server &uuml;ber einen anderen Port
              angesprochen werden, so musst Du diese Einstellung &auml;ndern.<br>
              <br>
              Du musst die Konfigurationsdatei, wenn Du sie fertig eingerichtet und abgespeichert hast,
              zwei mal auf Deinen Server hochladen.<br>
              Die eine Datei kommt in das Hauptverzeichnis, also zur \"index.php\" und die andere Datei
              kommt in das \"admin\"-Verzeichnis.<br>
              Wenn Du all dies getan hast, dann klicke bitte auf \"Weiter\"!<br>
              <br>
              <div align=\"right\"><a href=\"" . $_SERVER['PHP_SELF'] . "?type=install&page=2&mysqltables=$mysqltables\">Weiter >></a></div>
            ";
                }
                break;
            case '2':
                if ($configfile == 'YES') {
                    if ($mysql['host'] && $mysql['user'] && $mysql['database'] && $mysql['port']) {
                        if ($conn = mysqli_connect($mysql['host'], $mysql['user'], $mysql['password'], $mysql['database'], $mysql['port'])) {
                            $filecontent = "<?PHP\n  \$mysql['host'] = \"" . $mysql['host'] . "\";\n  \$mysql['user'] = \"" . $mysql['user'] . "\";\n  \$mysql['password'] = \"" . $mysql['password'] . "\";\n  \$mysql['database'] = \"" . $mysql['database'] . "\";\n  \$mysql['port'] = \"" . $mysql['port'] . "\";\n  \$settings['tablebg1'] = \"#000000\";\n  \$settings['footer']=\"\";\n \$version=2.00;\n?>";
                            if ($file = fopen('config.inc.php', 'w')) {
                                if (fwrite($file, $filecontent)) {
                                    fclose($file);
                                    if (!copy('config.inc.php', 'admin/config.inc.php')) {
                                        echo '<center><a href="javascript:history.back()">Die Konfigurationsdatei konnte nicht kopiert werden!</a></center>';
                                    } else {
                                        if ($mysqltables == 'YES') {
                                            function mysqli_die(): never
                                            {
                                                echo '<center><a href="javascript:history.back()">Die mySQL Tabellen konnten nicht erstellt werden!</a></center>';
                                                exit;
                                            }
                                            $conn->query('DROP TABLE pc_config');
                                            $conn->query('DROP TABLE pc_members');
                                            $conn->query('DROP TABLE pc_news');
                                            $conn->query('DROP TABLE pc_wars');
                                            $conn->query('CREATE TABLE pc_config (id int(11) NOT NULL auto_increment, clanname varchar(150) NOT NULL, clantag varchar(10) NOT NULL, url varchar(250) NOT NULL, serverpath varchar(250) NOT NULL, header varchar(200) NOT NULL, footer varchar(200) NOT NULL, tablebg1 varchar(7) NOT NULL, tablebg2 varchar(7) NOT NULL, tablebg3 varchar(7) NOT NULL, clrwon varchar(7) NOT NULL, clrdraw varchar(7) NOT NULL, clrlost varchar(7) NOT NULL, newslimit int(2) NOT NULL, warlimit int(2) NOT NULL, PRIMARY KEY (id))') || mysqli_die();
                                            $conn->query("CREATE TABLE pc_members (id int(11) NOT NULL auto_increment, nick varchar(100) NOT NULL, email varchar(200) NOT NULL, password varchar(25) NOT NULL, work varchar(200) NOT NULL, realname varchar(250) NOT NULL, icq int(10) DEFAULT '0' NOT NULL, homepage varchar(250) NOT NULL, age int(2) DEFAULT '0' NOT NULL, hardware text NOT NULL, info text NOT NULL, pic varchar(250) NOT NULL, member_add enum('YES','NO') DEFAULT 'NO' NOT NULL, member_edit enum('YES','NO') DEFAULT 'NO' NOT NULL, member_del enum('YES','NO') DEFAULT 'NO' NOT NULL, news_add enum('YES','NO') DEFAULT 'NO' NOT NULL, news_edit enum('YES','NO') DEFAULT 'NO' NOT NULL, news_del enum('YES','NO') DEFAULT 'NO' NOT NULL, wars_add enum('YES','NO') DEFAULT 'NO' NOT NULL, wars_edit enum('YES','NO') DEFAULT 'NO' NOT NULL, wars_del enum('YES','NO') DEFAULT 'NO' NOT NULL, superadmin enum('YES','NO') DEFAULT 'NO' NOT NULL, PRIMARY KEY (id))") || mysqli_die();
                                            $conn->query("CREATE TABLE pc_news (id int(11) NOT NULL auto_increment, time int(14) DEFAULT '0' NOT NULL, userid int(11) NOT NULL, nick varchar(100) NOT NULL, email varchar(250) NOT NULL, title varchar(150) NOT NULL, text text NOT NULL, PRIMARY KEY (id))") || mysqli_die();
                                            $conn->query("CREATE TABLE pc_wars (id int(11) NOT NULL auto_increment, enemy varchar(150) NOT NULL, enemy_tag varchar(10) NOT NULL, homepage varchar(250) NOT NULL, league varchar(150) NOT NULL, map1 varchar(100) NOT NULL, map2 varchar(100) NOT NULL, map3 varchar(100) NOT NULL, time int(14) DEFAULT '0' NOT NULL, report text NOT NULL, res1 varchar(50) NOT NULL, res2 varchar(50) NOT NULL, res3 varchar(50) NOT NULL, screen1 varchar(200) NOT NULL, screen2 varchar(200) NOT NULL, screen3 varchar(200) NOT NULL, PRIMARY KEY (id))") || mysqli_die();
                                            $conn->query("INSERT INTO pc_config (clanname, clantag, url, serverpath, header, footer, tablebg1, tablebg2, tablebg3, clrwon, clrdraw, clrlost, newslimit, warlimit) VALUES('PowerClan', 'PC', 'https://www.powerscripts.org/', '', 'header.pc', 'footer.pc', '#A0A0A0', '#F0F0F0', '#E0E0E0', '#008000', '#808080', '#800000', '10', '10')") || mysqli_die();
                                            echo 'Die Konfigurationsdateien und mySQL Tabellen wurden erfolgreich erstellt!<br><br><div align="right"><a href="' . $_SERVER['PHP_SELF'] . '?type=install&page=3">Weiter >></a></div>';
                                        } else {
                                            echo '
                            Da Du die mySQL Tabellen nicht automatisch angelegt haben wolltest musst Du die folgenden Schritte ausf&uuml;hren um PowerClan zu installieren:<br>
                            <br>
                            Als erstes solltest Du <a href="https://www.phpmyadmin.net" target="_install">phpMyAdmin</a> auf Deinem Webserver einrichten.
                            phpMyAdmin ist ein Tool um mySQL Datenbanken zu verwalten.<br>
                            <br>
                            Als n&auml;chstes importierst Du die Datei "powerclan.sql" in Deinen phpMyAdmin.<br>
                            Jetzt sollten alle n&ouml;tigen mySQL Tables vorhanden sein (pc_config, pc_members, pc_news und pc_wars).<br>
                            <br>
                            Wenn Du fertig bist, klicke auf Weiter!<br>
                            <br>
                            <div align="right"><a href="' . $_SERVER['PHP_SELF'] . '?type=install&page=3">Weiter >></a></div>
                          ';
                                        }
                                    }
                                } else {
                                    echo '<center><a href="javascript:history.back()">Die Konfigurationsdatei konnte nicht geschrieben werden!</a></center>';
                                }
                            } else {
                                echo '<center><a href="javascript:history.back()">Die Konfigurationsdatei konnte nicht erstellt werden!</a></center>';
                            }
                        } else {
                            echo '<center><a href="javascript:history.back()">Es konnte keine mySQL Verbindung hergestellt werden. Pr&uuml;fe ob alle Daten korrekt sind!</a></center>';
                        }
                    } else {
                        echo '<center><a href="javascript:history.back()">Bitte gib mySQL Server, User und Datenbank an!</a></center>';
                    }
                } else {
                    if ($mysqltables == 'YES') {
                        if (!file_exists('config.inc.php')) {
                            echo '<center>Es existiert keine Konfiguartionsdatei!</center>';
                            exit;
                        }
                        include(__DIR__ . '/config.inc.php');
                        if (!$conn = mysqli_connect($mysql['host'], $mysql['user'], $mysql['password'], $mysql['database'], $mysql['port'])) {
                            echo '<center>Es konnte keine mySQL Verbindung hergestellt werden!</center>';
                            exit;
                        }
                        // if (!mysqli_select_db($mysql["database"],$conn)) {
                        //   echo "<center>Es konnte keine mySQL Datenbank ausgew&auml;hlt werden!</center>";
                        //   exit;
                        // }

                        function mysqli_die(): never
                        {
                            echo '<center><a href="javascript:history.back()">Die mySQL Tabellen konnten nicht erstellt werden!</a></center>';
                            exit;
                        }
                        $conn->query('DROP TABLE pc_config');
                        $conn->query('DROP TABLE pc_members');
                        $conn->query('DROP TABLE pc_news');
                        $conn->query('DROP TABLE pc_wars');
                        $conn->query('CREATE TABLE pc_config (id int(11) NOT NULL auto_increment, clanname varchar(150) NOT NULL, clantag varchar(10) NOT NULL, url varchar(250) NOT NULL, serverpath varchar(250) NOT NULL, header varchar(200) NOT NULL, footer varchar(200) NOT NULL, tablebg1 varchar(7) NOT NULL, tablebg2 varchar(7) NOT NULL, tablebg3 varchar(7) NOT NULL, clrwon varchar(7) NOT NULL, clrdraw varchar(7) NOT NULL, clrlost varchar(7) NOT NULL, newslimit int(2) NOT NULL, warlimit int(2) NOT NULL, PRIMARY KEY (id))') || mysqli_die();
                        $conn->query("CREATE TABLE pc_members (id int(11) NOT NULL auto_increment, nick varchar(100) NOT NULL, email varchar(200) NOT NULL, password varchar(25) NOT NULL, work varchar(200) NOT NULL, realname varchar(250) NOT NULL, icq int(10) DEFAULT '0' NOT NULL, homepage varchar(250) NOT NULL, age int(2) DEFAULT '0' NOT NULL, hardware text NOT NULL, info text NOT NULL, pic varchar(250) NOT NULL, member_add enum('YES','NO') DEFAULT 'NO' NOT NULL, member_edit enum('YES','NO') DEFAULT 'NO' NOT NULL, member_del enum('YES','NO') DEFAULT 'NO' NOT NULL, news_add enum('YES','NO') DEFAULT 'NO' NOT NULL, news_edit enum('YES','NO') DEFAULT 'NO' NOT NULL, news_del enum('YES','NO') DEFAULT 'NO' NOT NULL, wars_add enum('YES','NO') DEFAULT 'NO' NOT NULL, wars_edit enum('YES','NO') DEFAULT 'NO' NOT NULL, wars_del enum('YES','NO') DEFAULT 'NO' NOT NULL, superadmin enum('YES','NO') DEFAULT 'NO' NOT NULL, PRIMARY KEY (id))") || mysqli_die();
                        $conn->query("CREATE TABLE pc_news (id int(11) NOT NULL auto_increment, time int(14) DEFAULT '0' NOT NULL, userid int(11) NOT NULL, nick varchar(100) NOT NULL, email varchar(250) NOT NULL, title varchar(150) NOT NULL, text text NOT NULL, PRIMARY KEY (id))") || mysqli_die();
                        $conn->query("CREATE TABLE pc_wars (id int(11) NOT NULL auto_increment, enemy varchar(150) NOT NULL, enemy_tag varchar(10) NOT NULL, homepage varchar(250) NOT NULL, league varchar(150) NOT NULL, map1 varchar(100) NOT NULL, map2 varchar(100) NOT NULL, map3 varchar(100) NOT NULL, time int(14) DEFAULT '0' NOT NULL, report text NOT NULL, res1 varchar(50) NOT NULL, res2 varchar(50) NOT NULL, res3 varchar(50) NOT NULL, screen1 varchar(200) NOT NULL, screen2 varchar(200) NOT NULL, screen3 varchar(200) NOT NULL, PRIMARY KEY (id))") || mysqli_die();
                        $conn->query("INSERT INTO pc_config (clanname, clantag, url, serverpath, header, footer, tablebg1, tablebg2, tablebg3, clrwon, clrdraw, clrlost, newslimit, warlimit) VALUES('PowerClan', 'PC', 'https://www.powerscripts.org/', '', 'header.pc', 'footer.pc', '#A0A0A0', '#F0F0F0', '#E0E0E0', '#008000', '#808080', '#800000', '10', '10')") || mysqli_die();
                        echo 'Die Konfigurationsdateien und mySQL Tabellen wurden erfolgreich erstellt!<br><br><div align="right"><a href="' . $_SERVER['PHP_SELF'] . '?type=install&page=3">Weiter >></a></div>';
                    } else {
                        echo '
                Da Du die mySQL Tabellen nicht automatisch angelegt haben wolltest musst Du die folgenden Schritte ausf&uuml;hren um PowerClan zu installieren:<br>
                <br>
                Als erstes solltest Du <a href="https://www.phpmyadmin.net" target="_install">phpMyAdmin</a> auf Deinem Webserver einrichten.
                phpMyAdmin ist ein Tool um mySQL Datenbanken zu verwalten.<br>
                <br>
                Als n&auml;chstes importierst Du die Datei "powerclan.sql" in Deinen phpMyAdmin.<br>
                Jetzt sollten alle n&ouml;tigen mySQL Tables vorhanden sein (pc_config, pc_members, pc_news und pc_wars).<br>
                <br>
                Wenn Du fertig bist, klicke auf Weiter!<br>
                <br>
                <div align="right"><a href="' . $_SERVER['PHP_SELF'] . '?type=install&page=3">Weiter >></a></div>
              ';
                    }
                }
                break;
            case '3':
                echo '
            <form action="' . $_SERVER['PHP_SELF'] . '?type=install&page=4" method="post">
            <table border="0" cellpadding="3" cellspacing="0">
            <tr><td>
            <b>Dein Nickname</b>
            </td><td>
            <input name="nickname" size="25" maxlength="100">
            </td></tr>
            <tr><td>
            <b>Deine E-Mail Adresse</b>
            </td><td>
            <input name="email" size="25" maxlength="250">
            </td></tr>
            <tr><td colspan="2" align="right">
            <input type=submit value="Weiter >>">
            </td></tr>
            </table>
            </form>
          ';
                break;
            case '4':
                if (!$nickname || !$email) {
                    echo '<center><a href="javascript:history.back()">Du musst alle Felder ausf&uuml;llen</a></center>';
                } else {
                    if (!file_exists('config.inc.php')) {
                        echo '<center>Es existiert keine Konfigurationsdatei!</center>';
                        exit;
                    }
                    include(__DIR__ . '/config.inc.php');
                    if (!$conn = mysqli_connect($mysql['host'], $mysql['user'], $mysql['password'], $mysql['database'], $mysql['port'])) {
                        echo '<center>Es konnte keine mySQL Verbindung hergestellt werden!</center>';
                        exit;
                    }
                    // if (!mysqli_select_db($mysql["database"],$conn)) {
                    //   echo "<center>Es konnte keine mySQL Datenbank ausgew&auml;hlt werden!</center>";
                    //   exit;
                    // }
                    $SCRIPT_NAME = '';
                    $SERVER_NAME = '';
                    $scriptname = str_replace('install.php', '', $SCRIPT_NAME);
                    $url = $SERVER_NAME . $scriptname;
                    mail('register@powerscripts.org', 'PowerClan installiert', 'Gerade eben wurde PowerClan auf ' . $url . ' von .' . $nickname . ' (' . $email . ') installiert!', 'FROM: PowerScript Autoregister <register@powerscripts.org');
                    $password = generate_password();
                    $password_coded = base64_encode((string) $password);
                    $insert = "INSERT INTO pc_members (nick, email, password, work, superadmin,realname,homepage,hardware, info, pic) VALUES ('" . $nickname . "', '" . $email . "', '" . $password_coded . "', 'Webmaster',  'YES','','','','','')";
                    $conn->query($insert);
                    mail((string) $email, 'PowerClan Installation', 'Herzlichen Glückwunsch,
 soeben hast Du PowerClan erfolgreich installiert.
 Dir wurden automatisch alle Adminrechte eingeraeumt. Deine Logindaten lauten:

 Nickname: ' . $nickname . '
 E-Mail: ' . $email . '
 Passwort: ' . $password . '

 Bitte loesche sofort nach erhalt dieser E-Mail die "install.php" von Deinem Webserver!
 -> NICHT AUF DIESE AUTOMATISCH GENERIERTE EMAIL ANTWORTEN!! <-', 'FROM: PowerClan Automailer <daemon@powerscripts.org>');
                    echo "Herzlichen Gl&uuml;ckwunsch,<br>soeben hast Du die PowerClan Installation erfolgreich abgeschlossen.<br>Dein Passwort ($password) wurde Dir per E-Mail zugesandt!<div align=\"right\"><a href=\"index.php\">Weiter >></a></div>";
                }
                break;
        }
        break;
    case 'update':
        if (!file_exists('config.inc.php')) {
            echo '<center>Es existiert keine Konfigurationsdatei!</center>';
            exit;
        }
        include(__DIR__ . '/config.inc.php');
        switch ($_GET['version']) {
            default:
                if ($version === 2.00) {
                    echo 'Sie verwenden schon die Version 2.00 und daher gibt kein Update für die Datenbank!';
                } else {
                    echo 'Bitte w&auml;hle im Men&uuml; von welcher Version Du updaten willst!';
                }
                break;
            case 1.0:
                if (file_exists('admin/config.inc.php')) {
                    include(__DIR__ . '/admin/config.inc.php');
                    if (!$conn = mysqli_connect($mysql['host'], $mysql['user'], $mysql['password'], $mysql['database'], $mysql['port'])) {
                        echo '<center>Es konnte keine mySQL Verbindung hergestellt werden!</center>';
                        exit;
                    }
                    $tableName = 'pc_members';
                    $fieldName = 'realname';
                    $query = "SHOW COLUMNS FROM $tableName LIKE '$fieldName'";
                    $result = $conn->query($query);

                    if ($result && $result->num_rows > 0) {
                        echo "Das Feld $fieldName existiert bereits in der Tabelle $tableName.";
                    } else {
                        // Das Feld existiert nicht, daher können Sie es hinzufügen.
                        $query = "ALTER TABLE $tableName ADD $fieldName VARCHAR(250) NOT NULL AFTER work";
                        $conn->query($query);
                        echo '
            <center>
            <a href="index.php">PowerClan wurde erfolgreich geupdatet!</a>
            </center>
        ';
                    }

                } else {
                    echo 'Es sind nicht alle erforderlichen Dateien vorhanden. Bitte &uuml;berpr&uuml;fe ob Du alle Dateien hochgeladen hast (Siehe auch Readme-Datei)';
                }
                break;
        }
        break;
}
?>

  </td></tr>
  </table>
</td></tr>
</table>
</center>
<br>
<center>
<small><a href="https://www.powerscripts.org" target="_new">PowerClan</a> &copy; Copyright 2001-20023 by <a href="mailto:info@powerscripts.org?subject=PowerClan Copyright">PowerScripts</a></small>
</center>

</body>
</html>