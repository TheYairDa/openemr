<?php
/**
 * Login screen.
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/gpl-license.php>;.
 *
 * @package OpenEMR
 * @author  Rod Roark <rod@sunsetsystems.com>
 * @author  Brady Miller <brady@sparmy.com>
 * @author  Kevin Yeh <kevin.y@integralemr.com>
 * @author  Scott Wakefield <scott.wakefield@gmail.com>
 * @author  ViCarePlus <visolve_emr@visolve.com>
 * @author  Julia Longtin <julialongtin@diasp.org>
 * @author  cfapress
 * @author  markleeds
 * @link    http://www.open-emr.org
 */

$fake_register_globals=false;
$sanitize_all_escapes=true;

$ignoreAuth=true;
include_once("../globals.php");
include_once("$srcdir/sql.inc");
?>
<html>
<head>
<?php html_header_show();?>
<title><?php echo text($openemr_name) . " " . xlt('Login'); ?></title>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<link rel="stylesheet" href="<?php echo $GLOBALS['assets_static_relative'] ?>/jquery-ui-1-11-4/themes/ui-darkness/jquery-ui.min.css" />
<link rel=stylesheet href="<?php echo $css_header;?>" type="text/css">
<link rel=stylesheet href="../themes/login.css" type="text/css">
<link rel="shortcut icon" href="<?php echo $webroot; ?>/interface/pic/favicon.ico" />

<script type="text/javascript" src="<?php echo $GLOBALS['assets_static_relative'] ?>/jquery-min-2-2-0/index.js"></script>
<script type="text/javascript" src="<?php echo $GLOBALS['assets_static_relative']  ?>/jquery-ui-1-11-4/jquery-ui.min.js"></script>

<script type="text/javascript">
var registrationTranslations = <?php echo json_encode(array(
  'title' => xla('OpenEMR Product Registration'),
  'pleaseProvideValidEmail' => xla('Please provide a valid email address'),
  'success' => xla('Success'),
  'registeredSuccess' => xla('Your installation of OpenEMR has been registered'),
  'submit' => xla('Submit'),
  'noThanks' => xla('No Thanks'),
  'registeredEmail' => xla('Registered email'),
  'registeredId' => xla('Registered id'),
  'genericError' => xla('Error. Try again later')
));
?>;

var registrationConstants = <?php echo json_encode(array(
  'webroot' => $GLOBALS['webroot']
))
?>;
</script>

<script type="text/javascript" src="<?php echo $webroot ?>/interface/product_registration/product_registration_service.js"></script>
<script type="text/javascript" src="<?php echo $webroot ?>/interface/product_registration/product_registration_controller.js"></script>

<script type="text/javascript">
    jQuery(document).ready(function() {
        var productRegistrationController = new ProductRegistrationController();
        productRegistrationController.getProductRegistrationStatus(function(err, data) {
            if (err) { return; }

            if (data.status === 'UNREGISTERED') {
                productRegistrationController.showProductRegistrationModal();
            }
        });
    });
</script>

<script language='JavaScript'>
function transmit_form()
{
    document.forms[0].submit();
}
function imsubmitted() {
<?php if (!empty($GLOBALS['restore_sessions'])) { ?>
 // Delete the session cookie by setting its expiration date in the past.
 // This forces the server to create a new session ID.
 var olddate = new Date();
 olddate.setFullYear(olddate.getFullYear() - 1);
 document.cookie = '<?php echo session_name() . '=' . session_id() ?>; path=/; expires=' + olddate.toGMTString();
<?php } ?>
    return false; //Currently the submit action is handled by the encrypt_form().
}
</script>

</head>
<body onload="javascript:document.login_form.authUser.focus();" class="body_image">
<span class="text"></span>
<center>

<form method="POST"
 action="../main/main_screen.php?auth=login&site=<?php echo attr($_SESSION['site_id']); ?>"
 target="_top" name="login_form" onsubmit="return imsubmitted();">

<input type='hidden' name='new_login_session_management' value='1' />

<?php
// collect groups
$res = sqlStatement("select distinct name from groups");
for ($iter = 0;$row = sqlFetchArray($res);$iter++)
	$result[$iter] = $row;
if (count($result) == 1) {
	$resvalue = $result[0]{"name"};
	echo "<input type='hidden' name='authProvider' value='" . attr($resvalue) . "' />\n";
}
// collect default language id
$res2 = sqlStatement("select * from lang_languages where lang_description = ?",array($GLOBALS['language_default']));
for ($iter = 0;$row = sqlFetchArray($res2);$iter++)
          $result2[$iter] = $row;
if (count($result2) == 1) {
          $defaultLangID = $result2[0]{"lang_id"};
          $defaultLangName = $result2[0]{"lang_description"};
}
else {
          //default to english if any problems
          $defaultLangID = 1;
          $defaultLangName = "English";
}
// set session variable to default so login information appears in default language
$_SESSION['language_choice'] = $defaultLangID;
// collect languages if showing language menu
if ($GLOBALS['language_menu_login']) {

        // sorting order of language titles depends on language translation options.
        $mainLangID = empty($_SESSION['language_choice']) ? '1' : $_SESSION['language_choice'];
        if ($mainLangID == '1' && !empty($GLOBALS['skip_english_translation']))
        {
          $sql = "SELECT *,lang_description as trans_lang_description FROM lang_languages ORDER BY lang_description, lang_id";
	  $res3=SqlStatement($sql);
        }
        else {
          // Use and sort by the translated language name.
          $sql = "SELECT ll.lang_id, " .
            "IF(LENGTH(ld.definition),ld.definition,ll.lang_description) AS trans_lang_description, " .
	    "ll.lang_description " .
            "FROM lang_languages AS ll " .
            "LEFT JOIN lang_constants AS lc ON lc.constant_name = ll.lang_description " .
            "LEFT JOIN lang_definitions AS ld ON ld.cons_id = lc.cons_id AND " .
            "ld.lang_id = ? " .
            "ORDER BY IF(LENGTH(ld.definition),ld.definition,ll.lang_description), ll.lang_id";
          $res3=SqlStatement($sql, array($mainLangID));
	}

        for ($iter = 0;$row = sqlFetchArray($res3);$iter++)
               $result3[$iter] = $row;
        if (count($result3) == 1) {
	       //default to english if only return one language
               echo "<input type='hidden' name='languageChoice' value='1' />\n";
        }
}
else {
        echo "<input type='hidden' name='languageChoice' value='".attr($defaultLangID)."' />\n";
}
?>

<table width="100%" height="99%">
<td align='center' valign='middle' width='34%'>
<div class="login-box" <?php if ($GLOBALS['extra_logo_login']) echo "style='width: 600px;'"; //enlarge width larger to fix the extra logo ?> >
<img class="logo-image" src="<?php echo $GLOBALS['webroot']?>/interface/pic/logo.png" />

<?php if ($GLOBALS['tiny_logo_1'] || $GLOBALS['tiny_logo_2']) { ?>
        <div id='tinylogocontainer' class='tinylogocontainer'>
                <?php if ($GLOBALS['tiny_logo_1'])  {echo $tinylogocode1;} if ($GLOBALS['tiny_logo_2']) {echo $tinylogocode2;} ?>
        </div>
<?php } ?>

<div class="title_name">
<?php if ($GLOBALS['show_label_login']) { ?>
        <?php echo text($openemr_name); ?>
<?php } ?>
</div>

<?php if ($GLOBALS['extra_logo_login']) { ?>
        <div class="logo-left"><?php echo $logocode;?></div>
<?php } ?>

<div class="table-right" <?php if ($GLOBALS['extra_logo_login']) echo "style='padding: 20px 20px;'"; //make room for the extra logo ?> >
<table width="100%">
<?php if (count($result) != 1) { ?>
<tr>
<td><span class="text"><?php echo xlt('Group:'); ?></span></td>
<td>
<select name=authProvider>
<?php
	foreach ($result as $iter) {
		echo "<option value='".attr($iter{"name"})."'>".text($iter{"name"})."</option>\n";
	}
?>
</select>
</td></tr>
<?php } ?>

<?php if (isset($_SESSION['loginfailure']) && ($_SESSION['loginfailure'] == 1)): ?>
<tr><td colspan='2' class='text' style='color:red'>
<?php echo xlt('Invalid username or password'); ?>
</td></tr>
<?php endif; ?>

<?php if (isset($_SESSION['relogin']) && ($_SESSION['relogin'] == 1)): ?>
<tr><td colspan='2' class='text' style='color:red;background-color:#dfdfdf;border:solid 1px #bfbfbf;text-align:center'>
<b><?php echo xlt('Password security has recently been upgraded.'); ?><br>
<?php echo xlt('Please login again.'); ?></b>
<?php unset($_SESSION['relogin']); ?>
</td></tr>
<?php endif; ?>

<tr>
<td><span class="text"><?php echo xlt('Username:'); ?></span></td>
<td>
<input class="entryfield" size="22" name="authUser">
</td></tr><tr>
<td><span class="text"><?php echo xlt('Password:'); ?></span></td>
<td>
<input class="entryfield" type="password" size="22" name="clearPass">
</td></tr>

<?php
if ($GLOBALS['language_menu_login']) {
if (count($result3) != 1) { ?>
<tr>
<td><span class="text"><?php echo xlt('Language'); ?>:</span></td>
<td>
<select class="entryfield" name=languageChoice size="1">
<?php
        echo "<option selected='selected' value='" . attr($defaultLangID) . "'>" . xlt('Default') . " - " . xlt($defaultLangName) . "</option>\n";
        foreach ($result3 as $iter) {
	        if ($GLOBALS['language_menu_showall']) {
                    if ( !$GLOBALS['allow_debug_language'] && $iter[lang_description] == 'dummy') continue; // skip the dummy language
                    echo "<option value='".attr($iter['lang_id'])."'>".text($iter['trans_lang_description'])."</option>\n";
		}
	        else {
		    if (in_array($iter[lang_description], $GLOBALS['language_menu_show'])) {
                        if ( !$GLOBALS['allow_debug_language'] && $iter['lang_description'] == 'dummy') continue; // skip the dummy language
		        echo "<option value='".attr($iter['lang_id'])."'>" . text($iter['trans_lang_description']) . "</option>\n";
		    }
		}
        }
?>
</select>
</td></tr>
<?php }} ?>

<tr><td>&nbsp;</td><td>
<input class="button large" type="submit" onClick="transmit_form()" value="<?php echo xla('Login');?>">

</td></tr>
<tr><td colspan='2' class='text' style='color:red'>
<?php
$ip=$_SERVER['REMOTE_ADDR'];
?>
</div>
</td></tr>
</table>

</div>
<div style="clear: both;"> </div>
<div class="version">
<a  href="../../acknowledge_license_cert.html" target="main"><?php echo xlt('Acknowledgments, Licensing and Certification'); ?></a>
</div>
</div>

<div class="product-registration-modal" style="display: none">
    <p class="context"><?php echo xlt("Register your installation with OEMR 501(c)(3) to receive important notifications, such as security fixes and new release announcements."); ?></p>
  <input placeholder="<?php echo xlt('email'); ?>" type="email" class="email" style="width: 100%; color: black" />
  <p class="message" style="font-style: italic"></p>
</div>

<div class="demo">
		<!-- Uncomment this for the OpenEMR demo installation
		<p><center>login = admin
		<br>password = pass
		-->
</div>
</td>
</tr>
</table>
</form>
</center>
</body>
</html>
