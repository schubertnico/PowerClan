<?php

declare(strict_types=1);

/**
 * PowerClan - PHP/MySQL Clan Portal
 * Add Member
 *
 * @copyright 2001-2026 PowerScripts
 * @license   MIT License
 * @link      https://github.com/schubertnico/PowerClan.git
 */

/** @var mysqli $conn */
/** @var string $admin_tbl1 */
/** @var string $admin_tbl2 */
/** @var string $admin_tbl3 */
/** @var array<string, mixed> $settings */
/** @var array<string, mixed> $pcadmin */

include __DIR__ . '/header.inc.php';
?>
<!--MAINPAGE-->

<?php
// CSRF protection
csrf_check();

if (($pcadmin['member_add'] ?? '') === 'YES' || ($pcadmin['superadmin'] ?? '') === 'YES') {
    $addmember = $_GET['addmember'] ?? '';

    if ($addmember === 'YES' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $nickname = trim($_POST['nickname'] ?? '');
        $email = trim($_POST['email'] ?? '');

        // Get permission checkboxes
        $member_add = ($_POST['member_add'] ?? '') === 'YES' ? 'YES' : 'NO';
        $member_edit = ($_POST['member_edit'] ?? '') === 'YES' ? 'YES' : 'NO';
        $member_del = ($_POST['member_del'] ?? '') === 'YES' ? 'YES' : 'NO';
        $news_add = ($_POST['news_add'] ?? '') === 'YES' ? 'YES' : 'NO';
        $news_edit = ($_POST['news_edit'] ?? '') === 'YES' ? 'YES' : 'NO';
        $news_del = ($_POST['news_del'] ?? '') === 'YES' ? 'YES' : 'NO';
        $wars_add = ($_POST['wars_add'] ?? '') === 'YES' ? 'YES' : 'NO';
        $wars_edit = ($_POST['wars_edit'] ?? '') === 'YES' ? 'YES' : 'NO';
        $wars_del = ($_POST['wars_del'] ?? '') === 'YES' ? 'YES' : 'NO';

        if (empty($nickname) || empty($email)) {
            echo '<div class="alert alert-danger" role="alert">'
                . 'Bitte gib Nickname und E-Mail an! '
                . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
            include __DIR__ . '/footer.inc.php';
            exit;
        }

        // Check for existing member using prepared statement
        $checkStmt = db_prepare($conn, 'SELECT id FROM pc_members WHERE email = ? OR nick = ?');
        $checkStmt->bind_param('ss', $email, $nickname);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult === false) {
            $checkStmt->close();
            throw new RuntimeException('Failed to get result');
        }

        if (mysqli_num_rows($checkResult) !== 0) {
            echo '<div class="alert alert-danger" role="alert">'
                . 'Es gibt schon einen Member mit dieser E-Mail oder diesem Nickname! '
                . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
            $checkStmt->close();
            include __DIR__ . '/footer.inc.php';
            exit;
        }
        $checkStmt->close();

        // Validate email
        if (!validate_email($email)) {
            echo '<div class="alert alert-danger" role="alert">'
                . 'Die angegebene E-Mail-Adresse ist ungültig! '
                . '<a class="alert-link" href="javascript:history.back()">Zurück</a></div>';
            include __DIR__ . '/footer.inc.php';
            exit;
        }

        // Generate secure random password
        $generatedPassword = bin2hex(random_bytes(8)); // 16 character password
        $passwordHash = password_hash($generatedPassword, PASSWORD_DEFAULT);

        // Insert member using prepared statement
        $sql = 'INSERT INTO pc_members (nick, email, password, work, member_add, member_edit, '
            . 'member_del, news_add, news_edit, news_del, wars_add, wars_edit, wars_del, '
            . 'superadmin, realname, homepage, hardware, info, pic) '
            . "VALUES (?, ?, ?, 'Fighter', ?, ?, ?, ?, ?, ?, ?, ?, ?, 'NO', '', '', '', '', '')";
        $insertStmt = db_prepare($conn, $sql);
        $insertStmt->bind_param(
            'ssssssssssss',
            $nickname,
            $email,
            $passwordHash,
            $member_add,
            $member_edit,
            $member_del,
            $news_add,
            $news_edit,
            $news_del,
            $wars_add,
            $wars_edit,
            $wars_del
        );
        $insertStmt->execute();
        $insertStmt->close();

        // Send email notification
        $adminNick = $pcadmin['nick'] ?? 'Admin';
        $clanname = $settings['clanname'] ?? 'PowerClan';
        $siteUrl = $settings['url'] ?? '';

        $subject = 'PowerClan Autoemail';
        $message = "Hallo {$nickname},

Du wurdest gerade von {$adminNick} als Member in das PowerClan System des {$clanname} Clans aufgenommen.
Du kannst Dich mit den folgenden Daten unter {$siteUrl} einloggen:

Nickname: {$nickname}
E-Mail: {$email}
Passwort: {$generatedPassword}

Das Passwort und Deine anderen Daten kannst Du jederzeit aendern.

-BITTE NICHT AUF DIESE AUTOMATISCH GENERIERTE EMAIL ANTWORTEN-";

        $headers = 'From: PowerClan Automailer <powerclan@powerscripts.org>';

        // Suppress mail errors if mail server not configured
        $ok = @mail($email, $subject, $message, $headers);

        if ($ok) {
            echo '<div class="alert alert-success" role="alert">'
                . 'Der Member wurde erfolgreich hinzugefügt und per E-Mail benachrichtigt! '
                . '<a class="alert-link" href="index.php">Zum Dashboard</a></div>';
        } else {
            echo '<div class="alert alert-warning" role="alert">'
                . '<strong>Achtung:</strong> Der Member wurde erfolgreich hinzugefügt, '
                . 'die E-Mail konnte aber nicht versendet werden. '
                . 'Bitte das generierte Passwort manuell an <code>' . e($email) . '</code> weiterreichen. '
                . '<a class="alert-link" href="index.php">Zum Dashboard</a></div>';
        }
    } else {
        $phpSelf = e($_SERVER['PHP_SELF']);
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-body-secondary">
                <h1 class="h4 mb-0">Member hinzufügen</h1>
            </div>
            <div class="card-body">
                <form action="<?php echo $phpSelf; ?>?addmember=YES" method="post" novalidate>
                    <?php echo csrf_field(); ?>
                    <div class="row mb-3">
                        <label for="nickname" class="col-sm-3 col-form-label">Nickname <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <input id="nickname" name="nickname" type="text" class="form-control" maxlength="100" required aria-describedby="nicknameHelp">
                            <div id="nicknameHelp" class="form-text">Der Nickname, unter dem der Member bekannt ist.</div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <label for="email" class="col-sm-3 col-form-label">E-Mail <span class="text-danger" aria-hidden="true">*</span></label>
                        <div class="col-sm-9">
                            <input id="email" name="email" type="email" class="form-control" maxlength="250" required aria-describedby="emailHelp">
                            <div id="emailHelp" class="form-text">Die korrekte E-Mail-Adresse des Members. An diese wird das Initialpasswort versendet.</div>
                        </div>
                    </div>
                    <fieldset class="row mb-4">
                        <legend class="col-sm-3 col-form-label pt-0">Adminrechte</legend>
                        <div class="col-sm-9">
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <h2 class="h6 text-uppercase text-body-secondary small">Member</h2>
                                    <div class="form-check"><input class="form-check-input" id="p_member_add" type="checkbox" name="member_add" value="YES"><label class="form-check-label" for="p_member_add">Member hinzufügen</label></div>
                                    <div class="form-check"><input class="form-check-input" id="p_member_edit" type="checkbox" name="member_edit" value="YES"><label class="form-check-label" for="p_member_edit">Member editieren</label></div>
                                    <div class="form-check mb-3"><input class="form-check-input" id="p_member_del" type="checkbox" name="member_del" value="YES"><label class="form-check-label" for="p_member_del">Member löschen</label></div>
                                    <h2 class="h6 text-uppercase text-body-secondary small">News</h2>
                                    <div class="form-check"><input class="form-check-input" id="p_news_add" type="checkbox" name="news_add" value="YES"><label class="form-check-label" for="p_news_add">News hinzufügen</label></div>
                                    <div class="form-check"><input class="form-check-input" id="p_news_edit" type="checkbox" name="news_edit" value="YES"><label class="form-check-label" for="p_news_edit">News editieren</label></div>
                                    <div class="form-check"><input class="form-check-input" id="p_news_del" type="checkbox" name="news_del" value="YES"><label class="form-check-label" for="p_news_del">News löschen</label></div>
                                </div>
                                <div class="col-12 col-md-6 mt-3 mt-md-0">
                                    <h2 class="h6 text-uppercase text-body-secondary small">Wars</h2>
                                    <div class="form-check"><input class="form-check-input" id="p_wars_add" type="checkbox" name="wars_add" value="YES"><label class="form-check-label" for="p_wars_add">Wars hinzufügen</label></div>
                                    <div class="form-check"><input class="form-check-input" id="p_wars_edit" type="checkbox" name="wars_edit" value="YES"><label class="form-check-label" for="p_wars_edit">Wars editieren</label></div>
                                    <div class="form-check"><input class="form-check-input" id="p_wars_del" type="checkbox" name="wars_del" value="YES"><label class="form-check-label" for="p_wars_del">Wars löschen</label></div>
                                </div>
                            </div>
                            <div class="form-text mt-2">
                                Wähle nur Rechte aus, die der neue Member tatsächlich braucht. Superadmin-Rechte werden hier nicht vergeben.
                            </div>
                        </div>
                    </fieldset>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary">Member hinzufügen</button>
                        <button type="reset" class="btn btn-outline-secondary">Daten zurücksetzen</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
} else {
    echo '<div class="alert alert-warning" role="alert">Du hast keinen Zugang zu dieser Funktion!</div>';
}
?>

<!--FOOTER FILE-->
<?php include __DIR__ . '/footer.inc.php'; ?>
