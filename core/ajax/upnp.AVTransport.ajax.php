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

  try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');
    
    if (!isConnect('admin')) {
      throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
    if (init('action') == 'setAVTransportURI') {
      $eqp = upnp::byId(init('eqLogicId'));
      if (!is_object($eqp)) {
        throw new Exception(__('Equipement', __FILE__).' '.init('eqLogicId').' '.__('non trouvée', __FILE__));
      }
      
      //On charge l'uri
      $cmd = $eqp->getCmd('action','SetAVTransportURI');
      if (!is_object($cmd)) {
        throw new Exception(__('Commande SetAVTransportURI introuvable pour l\'équipement', __FILE__).' '.init('eqLogicId'));
      }
      
      $option = array(
        'InstanceID' => '0',
        'CurrentURI' => init('uri'),
        'CurrentURIMetaData' => init('metadata'),
        'WaitResponse' => true
      );
      
      $cmd->execute($option);
      
      ajax::success();
    }
    
    if(init('action') == 'createAction')
    {
      $eqp = upnp::byId(init('eqLogicId'));
      if (!is_object($eqp)) {
        throw new Exception(__('Equipement', __FILE__).' '.init('eqLogicId').' '.__('non trouvée', __FILE__));
      }
      
      //On recherche la commande de base
      $upnpCmd = $eqp->getCmd('action',init('upnpAction'));
      if (!is_object($upnpCmd)) {
        throw new Exception(__('Commande ', __FILE__).init('upnpAction').__(' introuvable pour l\'équipement', __FILE__).' '.init('eqLogicId'));
      }
      
      $cmd = new upnpCmd();

      $cmd->setName(init('cmdName'));
      $cmd->setLogicalId('UpnpUserAction');
      $cmd->setEqLogic_id($eqp->getId());
      $cmd->setType('action');
      $cmd->setSubType('other');
      $cmd->setConfiguration('source','Plugin');
      $cmd->setConfiguration('upnpAction',$upnpCmd->getId());
      foreach($upnpCmd->getConfiguration('arguments') as $option)
      {
        $cmd->setConfiguration('ArgVal_'.$option['name'],init($option['name']));
      }
      $cmd->save();
      
      ajax::success();
    }
    
    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
  } catch (Exception $e) {
    ajax::error(displayExeption($e), $e->getCode());
  }
?>
