<?php

/**
 * Insertion methods for multivariate experiments.
 * 
 * @package     sfGoogleWebsiteOptimizerPlugin
 * @subpackage  experiment
 * @author      Kris Wallsmith <kris [dot] wallsmith [at] gmail [dot] com>
 * @version     SVN: $Id: sfGWOExperimentMultivariate.class.php 8348 2008-04-07 15:40:10Z Kris.Wallsmith $
 */
class sfGWOExperimentMultivariate extends sfGWOExperiment
{
  /**
   * Insert content for the test page.
   * 
   * @param   sfResponse $response
   */
  protected function insertTestPageContent($response)
  {
    $control = $this->getControlScript($this->key);
    $this->doInsert($response, $control, self::POSITION_TOP);
    
    $tracker = $this->getTrackerScript($this->uacct, $this->key, 'test');
    $this->doInsert($response, $tracker, self::POSITION_BOTTOM);
  }
  
  /**
   * Insert content for the conversion page.
   * 
   * @param   sfResponse $response
   */
  protected function insertConversionPageContent($response)
  {
    $tracker = $this->getTrackerScript($this->uacct, $this->key, 'goal');
    $this->doInsert($response, $tracker, self::POSITION_BOTTOM);
  }
  
}
