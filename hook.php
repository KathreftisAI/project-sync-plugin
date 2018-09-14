<?php

/**
 * Called when user click on Install - Needed
 */
function plugin_redminesync_install() {
    global $DB;
    $DB->query("
        CREATE TABLE IF NOT EXISTS `glpi_plugin_redminesync_synclog` (
            `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `project_id` int NOT NULL,
            `task_id` int NOT NULL,
            `rm_project_id` int NOT NULL COMMENT 'redmine project id',
            `rm_task_id` int NOT NULL COMMENT 'redmine task id',
            `created_at` datetime NOT NULL
        );
    ");

    CronTask::register('PluginRedminesyncSync', 'Syncredmine', HOUR_TIMESTAMP*24,
        array(
        'comment'   => 'Sync tickets from redmine',
        'mode'      => CronTask::MODE_EXTERNAL
    ));
	
    return true;
}

/**
 * Called when user click on Uninstall - Needed
 */
function plugin_redminesync_uninstall() { return true; }

function redminesync_item_can($param){
  $param->right=1;
  return true;
}
