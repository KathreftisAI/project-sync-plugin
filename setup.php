<?php

/**
 * Get the name and the version of the plugin - Needed
 */
function plugin_version_redminesync() {
   return array('name'           => "Redmine Sync",
                'version'        => '1.0.0',
                'author'         => '<a href="http://www.unotechsoft.com/">Unotech</a>',
                'license'        => 'GPLv2+',
                'homepage'       => 'http://felicityplatform.com/',
                'minGlpiVersion' => '9.2.4');
}

/**
 *  Check if the config is ok - Needed
 */
function plugin_redminesync_check_config() {
    return true;
}
 
/**
 * Check if the prerequisites of the plugin are satisfied - Needed
 */
function plugin_redminesync_check_prerequisites() {
    // Check that the GLPI version is compatible
    if (version_compare(GLPI_VERSION, '9.2.4', 'lt')) {
        echo "This plugin Requires GLPI >= 9.2.4";
        return false;
    } 
    return true;
}

/**
 * Init the hooks of the plugins -Needed
**/
function plugin_init_redminesync() 
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['redminesync'] = true;
    $PLUGIN_HOOKS['config_page']['redminesync']           = 'front/config.form.php';
}
