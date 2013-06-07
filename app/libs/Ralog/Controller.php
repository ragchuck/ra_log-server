<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Ralog;

/**
 * Description of Controller
 *
 * @author zoellm
 */
class Controller {
    
    /**
     *
     * @var \Slim\Slim
     */
    public $app;
    
    public function __constructor(\Slim\Slim &$app) {
        $this->app = $app;
    }
}
