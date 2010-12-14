<?php

/**
 * Insert experiments into responses when request parameters are matched.
 * 
 * @package     sfGoogleWebsiteOptimizerPlugin
 * @subpackage  filter
 * @author      Kris Wallsmith <kris [dot] wallsmith [at] gmail [dot] com>
 * @version     SVN: $Id: sfGWOFilter.class.php 10548 2008-07-31 16:53:59Z Kris.Wallsmith $
 */
class sfGWOFilter extends sfFilter
{
  /**
   * Insert appropriate experiment code.
   * 
   * @throws  sfConfigurationException If the configured experiment type cannot be found
   * 
   * @param   sfFilterChain $filterChain
   */
  public function execute($filterChain)
  {
    $filterChain->execute();
    
    // connect to each active experiment
    $prefix = 'app_sf_google_website_optimizer_plugin_';
    if (sfConfig::get($prefix.'enabled', false))
    {
      $request  = $this->context->getRequest();
      $response = $this->context->getResponse();
      
      foreach (sfConfig::get($prefix.'experiments', array()) as $name => $param)
      {
        // merge default with configured parameters
        $param = array_merge(array(
          'enabled' => true, 
          'type'    => null, 
          'key'     => null, 
          'uacct'   => sfConfig::get($prefix.'uacct'), 
          'pages'   => array()), $param);
        if ($param['enabled'])
        {
          // determine experiment class
          $classes = sfConfig::get($prefix.'classes', array());
          $classes = array_merge(array(
            'ab'           => 'sfGWOExperimentAB', 
            'multivariate' => 'sfGWOExperimentMultivariate'), $classes);
          
          if (isset($classes[$param['type']]))
          {
            $class = $classes[$param['type']];
            $experiment = new $class($name, $param);
            if ($experiment->connect($request))
            {
              $experiment->insertContent($response);
            }
          }
          else
          {
            throw new sfConfigurationException(sprintf('The experiment type "%s" was not found.', $param['type']));
          }
        }
      }
    }
  }
}
