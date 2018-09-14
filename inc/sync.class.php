<?php

class PluginRedminesyncSync extends CommonGLPI
{
    static $config=array();
    static $response_data = array();
    static $rightname = "plugin_redminesync";

    static function updateConfig($data){
        global $DB;
        $value = serialize(array(
            'url' => $data['url'],
            'key' => $data['key'],
            'hour' => $data['hour']
        ));
        $DB->query("UPDATE glpi_configs SET value='$value' WHERE context='unotech' AND name='redmine_data'");
        $frequency = $data['hour']*60*60;
        $DB->query("UPDATE glpi_crontasks SET frequency='$frequency' WHERE itemtype='PluginRedminesyncSync' AND name='Syncredmine'");
        return true;
    }

    static function getConfig(){
        if(count(self::$config)){
            return self::$config;
        } else{
            self::initConfig();
            return self::$config;
        }
    }
   
    static function initConfig(){
        global $DB;
        $result = $DB->query('SELECT * FROM glpi_configs WHERE context="unotech" AND name="redmine_data"');
        if($result->num_rows==0){
            self::$config = array(
                'url'=>'',
                'key'=>'',
                'hour'=>24
            );
            $config = serialize(self::$config);
            $DB->query("INSERT INTO glpi_configs SET context='unotech', name='redmine_data', value='$config'");
        } else{
            $result = $DB->request("SELECT * FROM glpi_configs WHERE context='unotech' AND name='redmine_data'");
            foreach ($result as $value) {
                self::$config = unserialize($value['value']);
                return;
            }
        }
    }

    static function cronSyncredmine($task){
        self::initConfig();
        self::syncProjects();
        self::syncTasks();
        return true;
    }

    // to sync projects
    static function syncProjects(){
        global $DB;
        if(self::$config['url']=='' || self::$config['key']==''){
            return false;
        }
        $ch = curl_init();
        $request_url = self::$config['url'].'/projects.json?key='.self::$config['key'];
        curl_setopt($ch, CURLOPT_URL,$request_url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $result = json_decode($response);

        // no projects
        if(NULL==$result || !count($result->projects)){
            return true;
        }

        $project_ids =array();

        foreach ($result->projects as $value) {
            $project_ids[]=$value->id;
        }
        $project_ids_str = implode(', ', $project_ids);
        $res = $DB->request("SELECT rm_project_id FROM glpi_plugin_redminesync_synclog WHERE rm_project_id IN ($project_ids_str)");
        $inserted_ids = array();
        foreach ($res as $projects){
            $inserted_ids[]=$projects['rm_project_id'];
        }
        
        foreach ($result->projects as $projects){
            if(in_array($projects->id, $inserted_ids)){
                self::updateProjects($projects);
            } else{
                self::addProjects($projects);
            }
        }
    }

    static function addProjects($data){
        global $DB;

        $name = $data->name;
        $content = $data->description;
        $date_mod = date('Y-m-d H:i:s', strtotime($data->updated_on));
        $date_creation = date('Y-m-d H:i:s', strtotime($data->created_on));
        $redmine_id = $data->id;
        $now = date('Y-m-d H:i:s');

        $create_project_sql = "INSERT INTO glpi_projects SET priority='3', name='$name', content='$content', `date`='$date_creation', date_mod='$date_mod', date_creation='$date_creation', users_id='2'";
        $DB->query($create_project_sql);
        $project_id = $DB->insert_id();
        
        $add_history_sql = "INSERT INTO glpi_plugin_redminesync_synclog SET rm_project_id='$redmine_id', project_id='$project_id', created_at='$now'";
        $DB->query($add_history_sql);
    }

    static function updateProjects($data){
        global $DB;

        $name = $data->name;
        $content = $data->description;
        $redmine_id = $data->id;

        $create_project_sql = "UPDATE glpi_projects SET name='$name', content='$content' WHERE id=
        (SELECT project_id FROM glpi_plugin_redminesync_synclog WHERE rm_project_id=$redmine_id LIMIT 1)";
        $DB->query($create_project_sql);
    }

    // to sync projects
    static function syncTasks(){
        global $DB;
        if(self::$config['url']=='' || self::$config['key']==''){
            return false;
        }
        $ch = curl_init();
        $request_url = self::$config['url'].'/issues.json?key='.self::$config['key'];
        curl_setopt($ch, CURLOPT_URL,$request_url );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $result = json_decode($response);

        // no issues
        if(NULL==$result || !count($result->issues)){
            return true;
        }

        // check if already synced
        $issue_ids=array();
        foreach ($result->issues as $issue){
            $issue_ids[] = $issue->id;
        }
        $issue_ids = implode(', ', $issue_ids);
        $res = $DB->request("SELECT rm_task_id FROM glpi_plugin_redminesync_synclog WHERE rm_task_id IN ($issue_ids)");
        $inserted_ids = array();
        foreach ($res as $issue){
            $inserted_ids[]=$issue['rm_task_id'];
        }
        
        foreach ($result->issues as $issue){
            if(in_array($issue->id, $inserted_ids)){
                self::updateTasks($issue);
            } else{
                self::addTasks($issue);
            }
        }
    }

    static function addTasks($issue){
        global $DB;
        $name = $issue->subject;
        $content = $issue->description;
        $start_date = date('Y-m-d H:i:s', strtotime($issue->start_date));
        $date_mod = date('Y-m-d H:i:s', strtotime($issue->updated_on));
        $redmine_id = $issue->id;
        $project_id = $issue->project->id;
        $now = date('Y-m-d H:i:s');

        $res = $DB->request("SELECT project_id, rm_project_id FROM glpi_plugin_redminesync_synclog WHERE rm_project_id=$project_id LIMIT 1");
        $project_id=0;
        if(!count($res)){
            return false;
        }
        $value = $res->next();
        $project_id = $value['project_id'];

        $create_task_sql = "INSERT INTO glpi_projecttasks SET name='$name', content='$content', `date`='$start_date', date_mod='$date_mod', users_id='2', projects_id='$project_id'";
        $DB->query($create_task_sql);
        $task_id = $DB->insert_id();
        
        $add_history_sql = "INSERT INTO glpi_plugin_redminesync_synclog SET rm_project_id='$redmine_id', project_id='$project_id', created_at='$now', task_id='$task_id', rm_task_id='$redmine_id'";
        $DB->query($add_history_sql);
    }

    static function updateTasks($data){
        global $DB;

        $name = $data->subject;
        $content = $data->description;
        $redmine_id = $data->id;

        $create_project_sql = "UPDATE glpi_projecttasks SET name='$name', content='$content' WHERE id=
        (SELECT task_id FROM glpi_plugin_redminesync_synclog WHERE rm_task_id=$redmine_id LIMIT 1)";
        $DB->query($create_project_sql);
    }

}
