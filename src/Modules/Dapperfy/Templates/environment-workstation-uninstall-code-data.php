<?php

/*************************************
*      Generated Autopilot file      *
*     ---------------------------    *
*Autopilot Generated By PTDeploy *
*     ---------------------------    *
*************************************/

namespace Core ;

class AutoPilotConfigured extends AutoPilot
{

    public $steps ;

    public function __construct()
    {
        $this->setSteps();
    }

    /* Steps */
    private function setSteps()
    {

        $this->steps =
          array(
              array ( "Git" => array(
                  "gitDeletorExecute" => true,
                  "gitDeletorCustomFolder" => "<%tpl.php%>dap_proj_cont_dir</%tpl.php%>"
              ), ) ,
              array ( "HostEditor" => array(
                  "hostEditorDeletionExecute" => true,
                  "hostEditorDeletionIP" => "<%tpl.php%>dap_apache_vhost_ip</%tpl.php%>",
                  "hostEditorDeletionURI" => "<%tpl.php%>dap_apache_vhost_url</%tpl.php%>.local",
              ) , ) ,
              array ( "ApacheVHostEditor" => array(
                  "virtualHostEditorDeletionExecute" => "boolean",
                  "virtualHostEditorDeletionDirectory" => "/etc/apache2/sites-available",
                  "virtualHostEditorDeletionTarget" => "<%tpl.php%>dap_apache_vhost_url</%tpl.php%>",
                  "virtualHostEditorDeletionVHostDisable" => false,
                  "virtualHostEditorDeletionSymLinkDirectory" => "/etc/apache2/sites-enabled",
                  "virtualHostEditorDeletionApacheCommand" => "apache2",
              ) , ) ,
              array ( "DBInstall" => array(
                  "dbDropExecute" => true,
                  "dbDropDBHost" => "<%tpl.php%>dap_db_ip_address</%tpl.php%>",
                  "dbDropDBName" => "<%tpl.php%>dap_db_name</%tpl.php%>",
                  "dbDropDBRootUser" => "****dap_db_admin_user_name****",
                  "dbDropDBRootPass" => "****dap_db_admin_user_pass****",
                  "dbDropUserExecute" => true,
                  "dbDropDBUser" => "<%tpl.php%>dap_db_app_user_name</%tpl.php%>",
              ) , ) ,
              array ( "ApacheControl" => array(
                  "apacheCtlRestartExecute" => true,
              ) , ) ,
          );
    }
}
