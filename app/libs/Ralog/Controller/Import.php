<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ralog\Controller;

/**
 * Description of Import
 *
 * @author zoellm
 */
class Import extends \Ralog\Controller {
    
    public function get_index($params) {
        return array('OK', $params);
    }
    
}