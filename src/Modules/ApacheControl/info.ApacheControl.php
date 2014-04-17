<?php

Namespace Info;

class ApacheControlInfo extends Base {

    public $hidden = false;

    public $name = "Apache Server Control";

    public function _construct() {
      parent::__construct();
    }

    public function routesAvailable() {
      return array( "ApacheControl" => array_merge(parent::routesAvailable(), array("start", "stop", "restart", "reload") ) );
    }

    public function routeAliases() {
      return array("apachecontrol"=>"ApacheControl", "apachectl"=>"ApacheControl", "apache-control"=>"ApacheControl",
          "apache-ctl"=>"ApacheControl");
    }

    public function helpDefinition() {
      $help = <<<"HELPDATA"
  This command is part of Default Modules and handles Apache Server Control Functions.

  ApacheControl, apachecontrol, apachectl

          - start
          Start the Apache server
          example: dapperstrano apachecontrol start

          - stop
          Stop the Apache server
          example: dapperstrano apachecontrol stop

          - restart
          Restart the Apache server
          example: dapperstrano apachecontrol restart

          - reload
          Reloads the Apache server configuration without restarting
          example: dapperstrano apachecontrol reload

HELPDATA;
      return $help ;
    }


}