<?php

include('../../../inc/includes.php');

$plugin = new Plugin();
if ($plugin->isActivated("redminesync")) {
    if (isset($_POST["update"]) && $_POST["update"]=='Update') {
        PluginRedminesyncSync::updateConfig($_POST);
        Session::addMessageAfterRedirect(__('Config updated successfully'), false, INFO);
        Html::back();
    } else {
        $config = PluginRedminesyncSync::getConfig();
        Html::header('RedmineConfig', '', "admin", "pluginredminesync");
        echo '<form method="post" name="helpdeskform" action="">'; ?>
        <table class="tab_cadre_fixehov">
            <tr>
            <td colspan="2">
                <h2>Redmine Config</h2>
            </td>
            </tr>
            <tr class='tab_bg_2'>
            <td>URL</td>
            <td>
                <input type="text" name="url" value="<?php echo $config['url']; ?>">
            </td>
            </tr>
            <tr class='tab_bg_2'>
            <td>Api key</td>
            <td>
                <input type="text" name="key" value="<?php echo $config['key']; ?>">
            </td>
            </tr>
            <tr class='tab_bg_2'>
            <td>Time to run (Interval)</td>
            <td>
                <select name="hour" class="form-control">
                    <?php
                    $default=$config['hour'];
                    for ($i=1; $i <=24 ; $i++) { 
                        $selected = $i==$default?' selected="selected" ':'';
                        echo "<option $selected value='$i'>$i Hour</option>";
                    }
                    ?>
                </select>
            </td>
            </tr>
            <tr>
            <td colspan="2">
                <center><input type="submit" value="Update" name="update" class="submit"></center>
            </td>
            </tr>
        </table>
        
        <?php Html::closeForm();
        Html::footer();
    }
} else {
    Html::header(__('Setup'), '', "config", "plugins");
    echo "<div align='center'><br><br>";
    echo "<img src=\"" . $CFG_GLPI["root_doc"] . "/pics/warning.png\" alt='warning'><br><br>";
    echo "<b>" . __('Please activate the plugin', 'redminesync') . "</b></div>";
    Html::footer();
}
