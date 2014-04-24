<?php

Namespace Model;

class ApacheVHostEditorLinuxMac extends Base {

    // Compatibility
    public $os = array("Linux", "Darwin") ;
    public $linuxType = array("any") ;
    public $distros = array("any") ;
    public $versions = array("any") ;
    public $architectures = array("any") ;

    // Model Group
    public $modelGroup = array("Default") ;

    private $vHostTemplate;
    private $docRoot;
    private $url;
    private $fileExtension;
    private $vHostIp;
    private $vHostForDeletion;
    private $vHostEnabledDir;
    private $apacheCommand;
    private $vHostDir = '/etc/apache2/sites-available' ; // no trailing slash
    private $vHostTemplateDir;
    private $vHostDefaultTemplates;

    public function __construct($params) {
      parent::__construct($params);
      $this->setVHostDefaultTemplates();
    }

    public function askWhetherToListVHost() {
        return $this->performVHostListing();
    }

    public function askWhetherToCreateVHost() {
        return $this->performVHostCreation();
    }

    public function askWhetherToDeleteVHost() {
        return $this->performVHostDeletion();
    }

    public function askWhetherToEnableVHost() {
        return $this->performVHostEnable();
    }

    public function askWhetherToDisableVHost() {
        return $this->performVHostDisable();
    }

    private function performVHostListing() {
        $this->vHostDir = $this->askForVHostDirectory();
        $this->vHostEnabledDir = $this->findVHostEnabledDirectory();
        $this->listAllVHosts();
        return true;
    }

    private function performVHostCreation() {
        if ( !$this->askForVHostEntry() ) { return false; }
        $this->docRoot = $this->askForDocRoot();
        $this->url = $this->askForHostURL();
        $this->vHostIp = $this->askForVHostIp();
        $this->fileExtension = $this->askForFileExtension();
        $this->vHostTemplateDir = $this->askForVHostTemplateDirectory();
        $this->selectVHostTemplate();
        $this->processVHost();
        if ( !$this->checkVHostOkay() ) { return false; }
        $this->vHostDir = $this->askForVHostDirectory();
        $this->attemptVHostWrite();
        if ( $this->askForEnableVHost() ) {
            $this->enableVHost(); }
        return true;
    }

    private function performVHostDeletion(){
        if ( !$this->askForVHostDeletion() ) { return false; }
        echo "Deleting vhost\n";
        $this->vHostDir = $this->askForVHostDirectory();
        $this->vHostForDeletion = $this->selectVHostInProjectOrFS();
        if ( self::areYouSure("Definitely delete VHost?") == false ) {
          return false; }
        if ( $this->askForDisableVHost() ) {
            $this->disableVHost(); }
        $this->attemptVHostDeletion();
        return true;
    }

    private function performVHostEnable() {
        if ( $this->askForEnableVHost() ) {
            $urlRay = $this->selectVHostInProjectOrFS() ;
            $this->url = $urlRay[0] ;
            $this->enableVHost(); }
        return true;
    }

    private function performVHostDisable(){
        if ( $this->askForDisableVHost() ) {
            $this->vHostForDeletion = $this->selectVHostInProjectOrFS();
            $this->disableVHost(); }
        return true;
    }

    private function askForVHostEntry() {
        if (isset($this->params["yes"]) && $this->params["yes"]==true) { return true ; }
        $question = 'Do you want to add a VHost?';
        return self::askYesOrNo($question);
    }

    private function askForVHostDeletion() {
        if (isset($this->params["yes"]) && $this->params["yes"]==true) { return true ; }
        $question = 'Do you want to delete VHost/s?';
        return self::askYesOrNo($question);
    }

    private function askForEnableVHost() {
        if (isset($this->params["yes"]) && $this->params["yes"]==true) { return true ; }
        if (isset($this->params["guess"]) && $this->params["guess"]==true) {
            if ($this->detectDebianApacheVHostFolderExistence()) {
                echo "You have a sites available dir, guessing you need to enable a Virtual Host.\n" ;
                return true ; }
            else { echo "You don't have a sites available dir, guessing you don't need to enable a Virtual Host.\n"; } }
        $question = 'Do you want to enable this VHost? (hint - ubuntu probably yes, centos probably no)';
        return self::askYesOrNo($question);
    }

    private function askForDisableVHost() {
        if (isset($this->params["yes"]) && $this->params["yes"]==true) { return true ; }
        if (isset($this->params["guess"]) && $this->params["guess"]==true) {
            if ($this->detectDebianApacheVHostFolderExistence()) {
                echo "You have a sites available dir, guessing you need to disable a Virtual Host.\n" ;
                return true ; }
            else { echo "You don't have a sites available dir, guessing you don't need to disable a Virtual Host.\n"; } }
        $question = 'Do you want to disable this VHost? (hint - ubuntu probably yes, centos probably no)';
        return self::askYesOrNo($question);
    }

    private function findVHostEnabledDirectory() {
        if ($this->detectDebianApacheVHostFolderExistence()) {
            echo "You have a sites available dir, so also listing available sites.\n" ;
            return "/etc/apache2/sites-available" ; }
        return null ;
    }

    private function askForDocRoot() {
        if (isset($this->params["vhe-docroot"])) { return $this->params["vhe-docroot"] ; }
        $question = 'What\'s the document root? Enter nothing for '.getcwd();
        $input = self::askForInput($question);
        return ($input=="") ? getcwd() : $input ;
    }

    private function askForHostURL() {
        if (isset($this->params["vhe-url"])) { return $this->params["vhe-url"] ; }
        $question = 'What URL do you want to add as server name?';
        return self::askForInput($question, true);
    }

    private function askForFileExtension() {
        if (isset($this->params["vhe-file-ext"])) { return $this->params["vhe-file-ext"] ; }
        $question = 'What File Extension should be used? Enter nothing for None (hint: ubuntu probably none centos, .conf)';
        $input = self::askForInput($question) ;
        return $input ;
    }

    private function askForVHostIp() {
        if (isset($this->params["vhe-ip-port"])) { return $this->params["vhe-ip-port"] ; }
        $question = 'What IP:Port should be set? Enter nothing for 127.0.0.1:80';
        $input = self::askForInput($question) ;
        return ($input=="") ? '127.0.0.1:80' : $input ;
    }

    private function checkVHostOkay(){
        if (isset($this->params["yes"]) && $this->params["yes"]==true) {
            echo $this->vHostTemplate."\n\nAssuming Okay due to yes parameter"; }
        $question = 'Please check VHost: '.$this->vHostTemplate."\n\nIs this Okay?";
        return self::askYesOrNo($question);
    }

    private function askForVHostDirectory(){
        if (isset($this->params["vhe-vhost-dir"])) { return $this->params["vhe-vhost-dir"] ; }
        $question = 'What is your VHost directory?';
        if ($this->detectDebianApacheVHostFolderExistence()) { $question .= ' Found "/etc/apache2/sites-available" - Enter nothing to use this';
            if (isset($this->params["guess"])) { return $this->vHostDir ; }
            $input = self::askForInput($question);
            return ($input=="") ? $this->vHostDir : $input ;  }
        if ($this->detectRHVHostFolderExistence()) { $question .= ' Found "/etc/httpd/vhosts.d" - Enter nothing to use this';
            if (isset($this->params["guess"])) { return "/etc/httpd/vhosts.d" ; }
            $input = self::askForInput($question);
            return ($input=="") ? "/etc/httpd/vhosts.d" : $input ;  }
        return self::askForInput($question, true);
    }

    private function askForVHostTemplateDirectory(){
        $question = 'What is your VHost Template directory? Enter nothing for default templates';
        if ($this->detectVHostTemplateFolderExistence()) {
            $question .= ' Found "'.$this->docRoot.'/build/config/dapperstrano/virtual-hosts" - Enter nothing to use this';
            $input = self::askForInput($question);
            return ($input=="") ? $this->vHostTemplateDir : $input ;  }
        else {
          $input = self::askForInput($question);
          return ($input=="") ? $this->vHostTemplateDir : $input ;
        }
    }

    private function detectDebianApacheVHostFolderExistence(){
        return file_exists("/etc/apache2/sites-available");
    }

    private function detectRHVHostFolderExistence(){
        return file_exists("/etc/httpd/vhosts.d");
    }

    private function detectVHostEnabledFolderExistence(){
        return file_exists("/etc/apache2/sites-enabled");
    }

    private function detectVHostTemplateFolderExistence(){
        return file_exists( $this->vHostTemplateDir = $this->docRoot."/build/config/dapperstrano/virtual-hosts");
    }

    private function attemptVHostWrite($virtualHostEditorAdditionFileExtension=null){
        $this->createVHost();
        $this->moveVHostAsRoot($virtualHostEditorAdditionFileExtension);
        $this->writeVHostToProjectFile($virtualHostEditorAdditionFileExtension);
    }

    private function attemptVHostDeletion(){
        $this->deleteVHostAsRoot();
        $this->deleteVHostFromProjectFile();
    }

    private function processVHost() {
        $replacements =  array('****WEB ROOT****'=>$this->docRoot,
            '****SERVER NAME****'=>$this->url, '****IP ADDRESS****'=>$this->vHostIp);
        $this->vHostTemplate = strtr($this->vHostTemplate, $replacements);
    }

    private function createVHost() {
        $tmpDir = '/tmp/'.DIRECTORY_SEPARATOR.'vhosttemp'.DIRECTORY_SEPARATOR;
        if (!file_exists($tmpDir)) {mkdir ($tmpDir, 0777, true);}
        return file_put_contents($tmpDir.'/'.$this->url, $this->vHostTemplate);
    }

    private function moveVHostAsRoot($virtualHostEditorAdditionFileExtension=null){
        $command = 'sudo mv '.'/tmp/'.DIRECTORY_SEPARATOR.'vhosttemp'.DIRECTORY_SEPARATOR.$this->url.' '.
            $this->vHostDir.'/'.$this->url.$virtualHostEditorAdditionFileExtension;
        return self::executeAndOutput($command);
    }

    private function deleteVHostAsRoot(){
        foreach ($this->vHostForDeletion as $vHost) {
            $command = 'sudo rm -f '.$this->vHostDir.'/'.$vHost;
            self::executeAndOutput($command, "VHost $vHost Deleted  if existed"); }
        return true;
    }

    private function writeVHostToProjectFile($virtualHostEditorAdditionFileExtension=null){
        if ($this->checkIsDHProject()){
            \Model\AppConfig::setProjectVariable("virtual-hosts", $this->url.$virtualHostEditorAdditionFileExtension); }
    }

    private function deleteVHostFromProjectFile(){
        if ($this->checkIsDHProject()){
            $allProjectVHosts = \Model\AppConfig::getProjectVariable("virtual-hosts");
            for ($i = 0; $i<=count($allProjectVHosts) ; $i++ ) {
                if (isset($allProjectVHosts[$i]) && in_array($allProjectVHosts[$i], $this->vHostForDeletion)) {
                    unset($allProjectVHosts[$i]); } }
            \Model\AppConfig::setProjectVariable("virtual-hosts", $allProjectVHosts); }
    }

    private function enableVHost(){
        $command = 'a2ensite '.$this->url;
        return self::executeAndOutput($command, "a2ensite $this->url done");
    }

    private function disableVHost(){
        if (!is_array($this->vHostForDeletion)) {
            $this->vHostForDeletion = array($this->vHostForDeletion) ; }
        foreach ($this->vHostForDeletion as $vHost) {
            $command = 'a2dissite '.$vHost;
            self::executeAndOutput($command, "a2dissite $vHost done"); }
        return true;
    }

    private function checkIsDHProject() {
        return file_exists('dhproj');
    }

    // @todo get project variable below is wrong
    private function listAllVHosts() {
        $projResults = ($this->checkIsDHProject()) ? \Model\AppConfig::getProjectVariable("virtual-hosts") : array() ;
        $scanned = scandir($this->vHostEnabledDir) ;
        $enabledResults = (count($scanned)) ? $scanned : array() ;
        $otherResults = scandir($this->vHostDir);
        $question = "Current Installed VHosts:\n";
        $i1 = $i2 = $i3 = 0;
        $availableVHosts = array();
        if (count($projResults)>0) {
            $question .= "--- Project Virtual Hosts: ---\n";
            foreach ($projResults as $result) {
                $question .= "($i1) $result\n";
                $i1++;
                $availableVHosts[] = $result;} }
        if (count($enabledResults)>0) {
            $question .= "--- Enabled Virtual Hosts: ---\n";
            foreach ($otherResults as $result) {
                if ($result === '.' or $result === '..') continue;
                $question .= "($i1) $result\n";
                $i1++;
                $availableVHosts[] = $result;} }
        if (count($otherResults)>0) {
            $question .= "--- All Available Virtual Hosts: ---\n";
            foreach ($otherResults as $result) {
                if ($result === '.' or $result === '..') continue;
                $question .= "($i1) $result\n";
                $i1++;
                $availableVHosts[] = $result;} }
        echo $question;
    }

    private function selectVHostInProjectOrFS() {
        if (isset($this->params["site"])) {
            return array($this->params["site"]) ; }
        $projResults = ($this->checkIsDHProject())
          ? \Model\AppConfig::getProjectVariable("virtual-hosts")
          : array() ;
        $otherResults = scandir($this->vHostDir);
        $question = "Please Choose VHost:\n";
        $i1 = $i2 = 0;
        $availableVHosts = array();
        if (count($projResults)>0) {
            $question .= "--- Project Virtual Hosts: ---\n";
            foreach ($projResults as $result) {
                $question .= "($i1) $result\n";
                $i1++;
                $availableVHosts[] = $result;} }
        if (count($otherResults)>0) {
            $question .= "--- All Virtual Hosts: ---\n";
            foreach ($otherResults as $result) {
                if ($result === '.' or $result === '..') continue;
                $question .= "($i1) $result\n";
                $i1++;
                $availableVHosts[] = $result;} }
        $validChoice = false;
        while ($validChoice == false) {
            if ($i2>0) { $question = "That's not a valid option, ".$question; }
            $input = self::askForInput($question) ;
            if ( array_key_exists($input, $availableVHosts) ){
                $validChoice = true;}
            $i2++; }
        return array($availableVHosts[$input]) ;
    }

    // @todo, this is ugly and possibly unneccessary
    private function selectVHostTemplate(){
        if (isset($this->params["vhe-template"])) { $this->vHostTemplate = $this->params["vhe-template"] ; }
        $vHostTemplateResults = (is_array($this->vHostTemplateDir) &&
        count($this->vHostTemplateDir)>0)
          ? scandir($this->vHostTemplateDir)
          : array() ;
        $question = "Please Choose VHost Template: \n";
        $i1 = $i2 = 0;
        $availableVHostTemplates = array();
        $question .= "--- Default Virtual Host Templates: ---\n";
        foreach ($this->vHostDefaultTemplates as $title => $data) {
          $question .= "($i1) $title\n";
          $i1++;
          $availableVHostTemplates[] = $title; }
        if (count($vHostTemplateResults)>0) {
            $question .= "--- Virtual Host Templates in Project: ---\n";
            foreach ($vHostTemplateResults as $result) {
                if ($result === '.' or $result === '..') continue;
                $question .= "($i1) $result\n";
                $i1++;
                $availableVHostTemplates[] = $result;} }
        $validChoice = false;
        while ($validChoice == false) {
            if ($i2==1) { $question = "That's not a valid option, ".$question; }
            $input = self::askForInput($question) ;
            if (array_key_exists($input, $availableVHostTemplates) ){
                $validChoice = true;}
            $i2++; }
        if (array_key_exists($availableVHostTemplates[$input], $this->vHostDefaultTemplates) ) {
          $this->vHostTemplate
            = $this->vHostDefaultTemplates[$availableVHostTemplates[$input]];
          return ; }
      $this->vHostTemplate = file_get_contents($this->vHostTemplateDir . '/' .
        $availableVHostTemplates[$input]);
    }

    private function setVHostDefaultTemplates() {

      $template1 = <<<'TEMPLATE1'
NameVirtualHost ****IP ADDRESS****
<VirtualHost ****IP ADDRESS****>
	ServerAdmin webmaster@localhost
	ServerName ****SERVER NAME****
	DocumentRoot ****WEB ROOT****
	<Directory ****WEB ROOT****>
		Options Indexes FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>
</VirtualHost>
TEMPLATE1;

      $template2 = <<<'TEMPLATE2'
NameVirtualHost ****IP ADDRESS****
<VirtualHost ****IP ADDRESS****>
	ServerAdmin webmaster@localhost
	ServerName ****SERVER NAME****
	DocumentRoot ****WEB ROOT****/src
	<Directory ****WEB ROOT****/src>
		Options Indexes FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>
</VirtualHost>
TEMPLATE2;

      $template3 = <<<'TEMPLATE3'
NameVirtualHost ****IP ADDRESS****
<VirtualHost ****IP ADDRESS****>
	ServerAdmin webmaster@localhost
	ServerName ****SERVER NAME****
	DocumentRoot ****WEB ROOT****/web
	<Directory ****WEB ROOT****/web>
		Options Indexes FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>
</VirtualHost>
TEMPLATE3;

        $template4 = <<<'TEMPLATE4'
NameVirtualHost ****IP ADDRESS****
<VirtualHost ****IP ADDRESS****>
	ServerAdmin webmaster@localhost
	ServerName ****SERVER NAME****
	DocumentRoot ****WEB ROOT****/www
	<Directory ****WEB ROOT****/www>
		Options Indexes FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>
</VirtualHost>
TEMPLATE4;

        $template5 = <<<'TEMPLATE5'
NameVirtualHost ****IP ADDRESS****
<VirtualHost ****IP ADDRESS****>
	ServerAdmin webmaster@localhost
	ServerName ****SERVER NAME****
	DocumentRoot ****WEB ROOT****/docroot
	<Directory ****WEB ROOT****/docroot>
		Options Indexes FollowSymLinks MultiViews
		AllowOverride All
		Order allow,deny
		allow from all
	</Directory>
</VirtualHost>
TEMPLATE5;

    $this->vHostDefaultTemplates = array(
      "docroot-no-suffix" => $template1,
      "docroot-src-sfx" => $template2,
      "docroot-web-suffix" => $template3,
      "docroot-www-suffix" => $template4,
      "docroot-docroot-suffix" => $template5
    );

    }

}