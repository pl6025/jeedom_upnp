<?php

/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
*/

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class upnp extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */
  public static function event()
  {
    $data = json_decode(file_get_contents('php://input'));
    log::add('upnp', 'debug', 'debug event : ' . json_encode($data));
    log::add('upnp', 'debug', 'Passage dans la fonction event ' . $data->eventType);
    if ($data->eventType == 'error')
    {
      log::add('upnp', 'error', $data->description);
      //throw new Exception('Plugwise event Error : ' . init('description'));
      return;
    }

    if (!isset($data->deviceUDN))
    {
      log::add('upnp', 'error', 'Le deviceUDN est necessaire impossible de traiter le message');
      throw new Exception('Upnp event Error : ' . 'Le deviceUDN est necessaire impossible de traiter le message');
      return;
    }

    if (!isset($data->serviceId)){
      log::add('upnp', 'error', 'Le ServiceId est necessaire impossible de traiter le message');
      throw new Exception('Upnp event Error : ' . 'Le ServiceId est necessaire impossible de traiter le message');
      return;
    }

    $eqp = eqlogic::byLogicalId($data->deviceUDN.'_'.$data->serviceId,'upnp');

    if (!is_object($eqp)) {
      log::add('upnp', 'info', 'création de l\'équipement ' . $data->deviceUDN.'_'.$data->serviceId);
      $eqp = new upnp();
      $eqp->setEqType_name('upnp');
      $eqp->setLogicalId($data->deviceUDN.'_'.$data->serviceId);
      $eqp->setName($data->deviceUDN.'_'.$data->serviceId);
      $eqp->setConfiguration('UDN', $data->deviceUDN);
      $eqp->setConfiguration('serviceId', $data->serviceId);
      $eqp->setConfiguration('customDisplay', true);
      $eqp->setConfiguration('displayUnmanagedCommand', true);
      $eqp->setConfiguration('standardDisplayOfCustomizedCommand', false);
      $eqp->setIsEnable(1);
      $eqp->save();
      event::add('upnp::includeDevice', $eqp->getId());
    }

    switch ($data->eventType)
    {
      case 'createAction':
        $cmd = $eqp->getCmd('action',$data->name);
        if (!is_object($cmd))
        {
  				$cmd = new upnpCmd();
  				$cmd->setName(__($data->name, __FILE__));
  				$cmd->setLogicalId($data->name);
  				$cmd->setEqLogic_id($eqp->getId());
  				$cmd->setType('action');
  				/*if (count($data->arguments) > 0 ) $cmd->setSubType('message');
          else $cmd->setSubType('other');*/
          $cmd->setSubType('other');
  				if ($data->fromDevice) $cmd->setIsVisible(1);
          else $cmd->setIsVisible(0);
          $cmd->setConfiguration('source',$data->fromDevice?'Device':'Template');
          $cmd->setConfiguration('arguments',$data->arguments);
  				$cmd->save();
        }

        //On vérifie si le retour d'action existe, sinon on le créer
        $cmd = $eqp->getCmd('info','LastResponse');
        if (!is_object($cmd))
        {
  				$cmd = new upnpCmd();
  				$cmd->setName(__('LastResponse', __FILE__));
  				$cmd->setLogicalId('LastResponse');
  				$cmd->setEqLogic_id($eqp->getId());
  				$cmd->setType('info');
  				$cmd->setSubType('string');
          $cmd->setIsVisible(1);
          $cmd->setConfiguration('source','Plugin');
  				$cmd->save();
        }
        break;

      case 'createInfo':
        $cmd = $eqp->getCmd('info',$data->name);
        if (!is_object($cmd))
        {
    			$cmd = new upnpCmd();
    			$cmd->setName(__($data->name, __FILE__));
    			$cmd->setLogicalId($data->name);
    			$cmd->setEqLogic_id($eqp->getId());
          $cmd->setType('info');
          if ($data->type == 'boolean')
            $cmd->setSubType('binary');
          else if ($data->type == 'ui1' || $data->type == 'ui2' || $data->type == 'ui4')
          {
            $cmd->setSubType('numeric');
            $cmd->setTemplate('dashboard', 'badge');
            $cmd->setTemplate('mobile', 'badge');
          }
          else $cmd->setSubType('string');
          if (substr($data->name,0,10) == 'A_ARG_TYPE') $cmd->setIsVisible(0);
          else if ($data->name == 'LastChange') $cmd->setIsVisible(0);
          else if ($data->fromDevice) $cmd->setIsVisible(1);
          else $cmd->setIsVisible(0);
          $cmd->setConfiguration('source',$data->fromDevice?'Device':'Template');
    			$cmd->save();
        }
        break;

      case 'updateInfo':
        $cmd = $eqp->getCmd('info',$data->name);
        if (!is_object($cmd))
        {
          $cmd = new upnpCmd();
          $cmd->setName(__($data->name, __FILE__));
          $cmd->setLogicalId($data->name);
          $cmd->setEqLogic_id($eqp->getId());
          $cmd->setType('info');
          if ($data->type == 'boolean')
            $cmd->setSubType('binary');
          else if ($data->type == 'ui1' || $data->type == 'ui2' || $data->type == 'ui4')
          {
            $cmd->setSubType('numeric');
            $cmd->setTemplate('dashboard', 'badge');
            $cmd->setTemplate('mobile', 'badge');
          }
          else $cmd->setSubType('string');
          if (substr($data->name,0,10) == 'A_ARG_TYPE') $cmd->setIsVisible(0);
          else if ($data->name == 'LastChange') $cmd->setIsVisible(0);
          else $cmd->setIsVisible(1);
          $cmd->setConfiguration('source',$data->fromDevice?'Device':'Template');
          $cmd->save();
        }
        log::add('upnp', 'debug', 'Event cmd '.$cmd->getHumanName().' with value '.$data->value);
        //$cmd->event($data->value);
        $cmd->event(htmlentities($data->value));
        break;

      case 'updateService':
        if (isset($data->friendlyName)) $eqp->setConfiguration('friendlyName', $data->friendlyName);
        if (isset($data->location)) $eqp->setConfiguration('location', $data->location);
        if (isset($data->additionalData)) $eqp->setConfiguration('additionalData', $data->additionalData);
        if (isset($data->icon)) $eqp->setConfiguration('icon', $data->icon);
        if (isset($data->description)) $eqp->setConfiguration('description', $data->description);
        if (isset($data->isOnline)) $eqp->setConfiguration('isOnline',$data->isOnline?true:false);
        $eqp->save();
        break;
    }
  }

  public static function offlineAll()
  {
    foreach (eqlogic::byType('upnp') as $eqp)
    {
      $eqp->setConfiguration('isOnline',false);
      $eqp->save();
    }
  }

  public static function health() {
    $return = array();
    $urlService = '127.0.0.1';
    $servicePort = config::byKey('servicePort', 'upnp');
    if ($servicePort == '') $servicePort = 5002;

    $fp = fsockopen($urlService, $servicePort, $errno, $errstr);

    $return[] = array(
      'test' => __('Serveur upnp', __FILE__),
      'result' => ($fp) ?  __('OK', __FILE__) : (__('Erreur : ', __FILE__)).$errno.', '.$errstr,
      'advice' => ($fp) ? '' : __('Indique si le serveur upnp est en route', __FILE__),
      'state' => $fp,
    );
    fclose($fp);
    return $return;
  }

  public static function deamon_info() {
    $return = array();
    $return['log'] = 'upnp_deamon';
    $return['state'] = 'nok';
    $pid = trim( shell_exec ('ps ax | grep "upnp/node/upnpDaemon.js" | grep -v "grep" | wc -l') );
    if ($pid != '' && $pid != '0') {
      $return['state'] = 'ok';
    }
    $return['launchable'] = 'ok';
    return $return;
  }

  public static function dependancy_info() {
    $return = array();
    $return['log'] = 'upnp_dep';

    $xml2js = realpath(dirname(__FILE__) . '/../../node/node_modules/xml2js');
    $request = realpath(dirname(__FILE__) . '/../../node/node_modules/request');
    $ip = realpath(dirname(__FILE__) . '/../../node/node_modules/ip');
    $portfinder = realpath(dirname(__FILE__) . '/../../node/node_modules/portfinder');
    $htmlentities = realpath(dirname(__FILE__) . '/../../node/node_modules/html-entities');

    $return['progress_file'] = '/tmp/upnp_dep';
    if (is_dir($ip) && is_dir($xml2js)  && is_dir($portfinder) && is_dir($request) && is_dir($htmlentities)){
      $return['state'] = 'ok';
    } else {
      $return['state'] = 'nok';
    }
    return $return;
  }

  public static function dependancy_install() {
    log::add('upnp','info','Installation des dépéndances nodejs');
    $resource_path = realpath(dirname(__FILE__) . '/../../resources');
    passthru('/bin/bash ' . $resource_path . '/nodejs.sh ' . $resource_path . ' > ' . log::getPathToLog('upnp_dep') . ' 2>&1 &');
  }

  public static function deamon_start() {
    self::deamon_stop();
    //On met tous les equipements OffLine
    upnp::offlineAll();
    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok') {
      throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
    }
    log::add('upnp', 'info', 'Lancement du démon upnp');

    $servicePort = config::byKey('servicePort', 'upnp');
    if ($servicePort == '') $servicePort = 5002;
    $cmdTimeout = config::byKey('cmdTimeout', 'upnp');
    if ($cmdTimeout < 5 || $cmdTimeout > 20) $cmdTimeout = 10;
    $url  = network::getNetworkAccess().'/core/api/jeeApi.php?api='.config::byKey('api');
    upnp::launch_svc($url, $servicePort, $cmdTimeout);
  }

  public static function launch_svc($url, $servicePort, $cmdTimeout)
  {
    $logLevel = log::convertLogLevel(log::getLogLevel('upnp'));
    //$logLevel = log::getLogLevel('upnp');
    $upnp_path = realpath(dirname(__FILE__) . '/../../node');
    $cmd = 'nice -n 19 nodejs ' . $upnp_path . '/upnpDaemon.js ' . $url .' '. $servicePort . ' ' . $logLevel . ' ' . $cmdTimeout;

    log::add('upnp', 'debug', 'Lancement démon upnp : ' . $cmd);

    $result = exec('nohup ' . $cmd . ' >> ' . log::getPathToLog('upnp_deamon') . ' 2>&1 &');
    if (strpos(strtolower($result), 'error') !== false || strpos(strtolower($result), 'traceback') !== false) {
      log::add('upnp', 'error', $result);
      return false;
    }

    $i = 0;
    while ($i < 30) {
      $deamon_info = self::deamon_info();
      if ($deamon_info['state'] == 'ok') {
        break;
      }
      sleep(1);
      $i++;
    }
    if ($i >= 30) {
      log::add('upnp', 'error', 'Impossible de lancer le démon upnp, vérifiez le port', 'unableStartDeamon');
      return false;
    }
    message::removeAll('upnp', 'unableStartDeamon');
    log::add('upnp', 'info', 'Démon upnp lancé');
    return true;
  }

  public static function deamon_stop() {
    log::add('upnp', 'info', 'Arrêt du service upnpDaemon');
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] == 'ok') {
      $msg = array();
      $msg['command'] = 'stopDaemon';
      upnp::sendToDaemon(json_encode($msg));
      sleep(1);
    }
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] == 'ok') {
      sleep(1);
      exec('kill $(ps aux | grep "upnp/node/upnpDaemon.js" | awk \'{print $2}\')');
    }
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] == 'ok') {
      sleep(1);
      exec('kill -9 $(ps aux | grep "upnp/node/upnpDaemon.js" | awk \'{print $2}\')');
    }
    $deamon_info = self::deamon_info();
    if ($deamon_info['state'] == 'ok') {
      sleep(1);
      exec('sudo kill -9 $(ps aux | grep "upnp/node/upnpDaemon.js" | awk \'{print $2}\')');
    }
    //Normalement non nécessaire mais au cas ou
    upnp::offlineAll();
  }

  public static function sendToDaemon( $msg, $waitResponse = false ) {
    $response = '';
    $urlService = '127.0.0.1';
    $servicePort = config::byKey('servicePort', 'upnp');
    if ($servicePort == '') $servicePort = 5002;
    $response = '';
    log::add('upnp', 'debug', $msg);
    $fp = fsockopen($urlService, $servicePort, $errno, $errstr);
    if (!$fp) {
      log::add('upnp','error',$errno.' - '.$errstr);
    } else {
      fwrite($fp, $msg);
      if ($waitResponse)
      {
        while (!feof($fp)) {
          $response = $response.(fgets($fp, 128));
        }
      }
      fclose($fp);
    }
    if ($waitResponse) log::add('upnp','debug','reponse : '.$response);
    return $response;
  }

  public function toHtml($_version = 'dashboard')
  {
    $replace = $this->preToHtml($_version);
    if (!is_array($replace)) {
      return $replace;
    }
    $version = jeedom::versionAlias($_version);
    $cmd_html = '';
    $br_before = 0;
    $type = null;

    if ($this->getConfiguration('isOnline') != true) $type = 'info';

    $processedCommand = array();
    $processedService = false;

    //On traite le service ContentDirectory
    if ($_version == 'dashboard' && strpos($this->getConfiguration('serviceId'),'ContentDirectory') !== false && $this->getConfiguration('customDisplay'))
    {
      $cmdReplace = array();
      $browseCMD = $this->getCmd('action','Browse');
      if (is_object($browseCMD))
      {
        $cmdReplace["#cmd_browse_id#"] = $browseCMD->getId();
      }

      $searchCMD = $this->getCmd('action','Search');
      if (is_object($searchCMD))
      {
        $cmdReplace["#cmd_search_id#"] = $searchCMD->getId();
      }
      else
      {
        $cmdReplace["#cmd_search_id#"] = $browseCMD->getId().'_search';
        $cmdReplace["#search_disable#"] = '1';
      }

      $processedCommand = array('Browse','Search','GetSearchCapabilities','GetSortCapabilities','GetSystemUpdateID','SearchCapabilities','SortCapabilities','SystemUpdateID');
      $processedService = true;

      $cmd_html .= template_replace($cmdReplace,getTemplate('core', $version, 'cmd.upnp.ContentDirectory', 'upnp'));
    }
    //On traite le service AVTransport
    if ($_version == 'dashboard' && strpos($this->getConfiguration('serviceId'),'AVTransport') !== false && $this->getConfiguration('customDisplay'))
    {
      $cmdReplace = array();
      $cmdReplace["#cmd_play_id#"] = $this->getCmd('action','Play')->getId();
      $cmdReplace["#cmd_pause_id#"] = $this->getCmd('action','Pause')->getId();
      $cmdReplace["#cmd_stop_id#"] = $this->getCmd('action','Stop')->getId();
      $cmdReplace["#cmd_seek_id#"] = $this->getCmd('action','Seek')->getId();
      $cmdReplace["#cmd_previous_id#"] = $this->getCmd('action','Previous')->getId();
      $cmdReplace["#cmd_next_id#"] = $this->getCmd('action','Previous')->getId();

      $cmdReplace["#cmd_relativeTimePosition_id#"] = $this->getCmd('info','RelativeTimePosition')->getId();
      $cmdReplace["#cmd_relativeTimePosition_value#"] = $this->getCmd('info','RelativeTimePosition')->execCmd();

      $cmdReplace["#cmd_currentTrackDuration_id#"] = $this->getCmd('info','CurrentTrackDuration')->getId();
      $cmdReplace["#cmd_currentTrackDuration_value#"] = $this->getCmd('info','CurrentTrackDuration')->execCmd();

      $cmdReplace["#cmd_transportState_id#"] = $this->getCmd('info','TransportState')->getId();
      $cmdReplace["#cmd_transportState_value#"] = $this->getCmd('info','TransportState')->execCmd();

      $cmdMetaDataReplace = array();
      $cmdMetaDataReplace["#id#"] = $this->getCmd('info','CurrentTrackMetaData')->getId();
      $tempCmd = template_replace($cmdMetaDataReplace,getTemplate('core', $version, 'cmd.upnp.didl-lite', 'upnp'));
      $cmdReplace["#CurrentTrackMetaData#"] = $tempCmd;

      $cmdMetaDataReplace = array();
      $cmdMetaDataReplace["#id#"] = $this->getCmd('info','AVTransportURIMetaData')->getId();
      $tempCmd = template_replace($cmdMetaDataReplace,getTemplate('core', $version, 'cmd.upnp.didl-lite', 'upnp'));
      $cmdReplace["#AVTransportURIMetaData#"] = $tempCmd;

      $processedCommand = array('AbsoluteCounterPosition','AbsoluteTimePosition','AVTransportURI','AVTransportURIMetaData','CurrentMediaDuration'/*,
        'CurrentRecordQualityMode','CurrentTrack'*/,'CurrentTrackDuration','CurrentTrackMetaData','CurrentTrackURI',
        'CurrentTransportActions','GetCurrentTransportActions','GetDeviceCapabilities','GetMediaInfo','GetPositionInfo',
        'GetTransportInfo','GetTransportSettings'/*,'LastChange'*/,'Next'/*,'NextAVTransportURI','NextAVTransportURIMetaData',
        'NumberOfTracks'*/,'Pause','Play'/*,'PlaybackStorageMedium','PossiblePlaybackStorageMedia','PossibleRecordQualityModes',
        'PossibleRecordStorageMedia'*/,'Previous'/*,'RecordMediumWriteStatus','RecordStorageMedium'*/,'RelativeCounterPosition','RelativeTimePosition',
        'Seek','SetAVTransportURI','Stop','TransportPlaySpeed','TransportState','TransportStatus');

      $processedService = true;

      $cmd_html .= template_replace($cmdReplace,getTemplate('core', $version, 'cmd.upnp.AVTransport', 'upnp'));
    }

    //Ensuite on traite les commandes génériques
    foreach ($this->getCmd($type, null, true) as $cmd) {
      if (isset($replace['#refresh_id#']) && $cmd->getId() == $replace['#refresh_id#']) {
        continue;
      }

      if ($processedService &&
        ((in_array($cmd->getLogicalId(),$processedCommand)) && !$this->getConfiguration('standardDisplayOfCustomizedCommand'))
        ||
        (!(in_array($cmd->getLogicalId(),$processedCommand)) && !$this->getConfiguration('displayUnmanagedCommand'))
        ) continue; // && (!$this->getConfiguration('displayUnmanagedCommand'))) continue; // || in_array($cmd->getLogicalId(),$processedCommand))) continue;

      if ($br_before == 0 && $cmd->getDisplay('forceReturnLineBefore', 0) == 1) {
        $cmd_html .= '<br/>';
      }

      $args = $cmd->getConfiguration('arguments');

      if ($cmd->getType()=='action' && count($args) > 0)
      {
        $cmdUID = 'cmd' . $cmd->getId() . eqLogic::UIDDELIMITER . mt_rand() . eqLogic::UIDDELIMITER;

        $cmd_html .= '<div class="cmd" data-type="action" data-subtype="upnp" data-cmd_id="'.$this->getId().'" data-cmd_uid="'.$cmdUID.'" style="display:flex;flex-direction: row;justify-content: center;border:1px;border-color:black red;">';
        $cmd_html .= '<div style="display:flex;flex-direction: row;justify-content: center;">';
        foreach($args as $option)
        {
          $cmd_html .= '<div style="margin-left:2px;margin-right:2px;">';
          $cmd_html .= '<label class="control-label">'. $option['name'] .'</label>';

          if (isset($option['allowedValue']) && count($option['allowedValue']) > 0)
          {
            $cmd_html .= '<select class="form-control '. $option['name'] .'">';
            foreach($option['allowedValue'] as $val)
            {
              $cmd_html .= '<option value="' .$val. '">' .$val. '</option>';
            }
            $cmd_html .= '</select>';
          }
          else if ($option['type'] == 'ui1' || $option['type'] == 'ui2' || $option['type'] == 'ui4')
          {
            $cmd_html .= '<input class="form-control input-sm '. $option['name'] .'" type="number" placeholder="'.__('Value', __FILE__).'" value=0 />';
          }
          else
          {
            $cmd_html .= '<input class="form-control input-sm '. $option['name'] .'" type="string" placeholder="'.__('Value', __FILE__).'" />';
          }
          $cmd_html .= '</div>';
        }
        $cmd_html .= '</div>';
        $cmd_html .= '<a class="btn btn-sm btn-default execute cmdName tooltips" title="'.$cmd->getName().'" style="background-color:'.$replace['#cmd-background-color#'].' !important;border-color : transparent !important;">'.$cmd->getName().'</a>';//style="background-color:#cmdColor#
        $cmd_html .= '</div>';

        $cmd_html .= '<script>';
        $param = '{ id : '.$cmd->getId().', value : {';
        foreach($args as $option)
        {
          $param .= $option['name'].' : $(\'.cmd[data-cmd_uid='.$cmdUID.'] .'.$option['name'].'\').value(), ';
        }
        $param .= 'rien : \'rien\'}}';
        $cmd_html .= '$(\'.cmd[data-cmd_uid='.$cmdUID.']:last .execute\').on(\'click\', function() {';
        $cmd_html .= 'jeedom.cmd.execute('.$param.');';
        $cmd_html .= '});';
        $cmd_html .= '</script>';
      }
      else $cmd_html .= $cmd->toHtml($_version, '', $replace['#cmd-background-color#']);

      $br_before = 0;
      if ($cmd->getDisplay('forceReturnLineAfter', 0) == 1) {
        $cmd_html .= '<br/>';
        $br_before = 1;
      }
    }

    $replace['#cmd#'] = $cmd_html;
    return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'eqLogic')));
  }

  /*     * *********************Méthodes d'instance************************* */
  /*
	* Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
	public function toHtml($_version = 'dashboard') {

}
*/

/*     * **********************Getteur Setteur*************************** */
}

class upnpCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */
  /*public function preSave() {

	}*/

	/*public function postSave() {

	}*/

	/*
	* Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	public function dontRemoveCmd() {
	return true;
}
*/

	public function execute($_options = array()) {
		log::add('upnp', 'info', 'execute '.$this->getLogicalId().' avec option : '.json_encode($_options));
		switch ($this->getType()) {
			case 'action':
        if ($this->getEqLogic()->getConfiguration('isOnline') == false) throw new Exception("Error Processing Request, equipment is offline", 1);
        $msg = array();
        $msg['command'] = 'executeAction';
        $msg['UDN'] = $this->getEqLogic()->getConfiguration('UDN');
        $msg['serviceID'] = $this->getEqLogic()->getConfiguration('serviceId');
        $msg['actionName'] = $this->getLogicalId();
        $msg['options'] = array();
        /*if (isset($_options['message'])) $param = $_options['message']; //Pour une gestion via les scenario
        else $param = $_options;
        log::add('upnp', 'info', 'params '.json_encode($param));*/
        $param = $_options;
        foreach($this->getConfiguration('arguments') as $option)
        {
          $msg['options'][$option['name']] = $param[$option['name']];
        }
        if (!isset($param['waitResponse'])) $param['waitResponse'] = false;
        log::add('upnp', 'debug', 'waitReponse : '.($param['waitResponse']?'true':'false'));
        return upnp::sendToDaemon(json_encode($msg),$param['waitResponse']?true:false);
				break;
		}
	}
/*     * **********************Getteur Setteur*************************** */
}

?>