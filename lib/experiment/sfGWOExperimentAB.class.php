<?php

/**
 * Insertion methods for A/B experiments.
 * 
 * @package     sfGoogleWebsiteOptimizerPlugin
 * @subpackage  experiment
 * @author      Kris Wallsmith <kris [dot] wallsmith [at] gmail [dot] com>
 * @version     SVN: $Id: sfGWOExperimentAB.class.php 8350 2008-04-07 18:03:28Z Kris.Wallsmith $
 */
class sfGWOExperimentAB extends sfGWOExperiment
{
  /**
   * Insert content for the original page.
   * 
   * @param   sfResponse $response
   */
  protected function insertOriginalPageContent($response)
  {
    $control = $this->getControlScript($this->key);
    
    // insert a/b function
    $lines = explode("\n", $control);
    $end = array_pop($lines);
    $lines[] = '<script>utmx("url", \'A/B\')</script>';
    $lines[] = $end;
    $control = join("\n", $lines);
    
    $this->doInsert($response, $control, self::POSITION_TOP);
    
    $tracker = $this->getTrackerScript($this->uacct, $this->key, 'test');
    $this->doInsert($response, $tracker, self::POSITION_TOP);
  }
  
  /**
   * Insert content for a variation page.
   * 
   * @param   sfResponse $response
   */
  protected function insertVariationPageContent($response)
  {
    $tracker = $this->getTrackerScript($this->uacct, $this->key, 'test');
    $this->doInsert($response, $tracker, self::POSITION_TOP);
  }
  
  /**
   * Insert content for the conversion page.
   * 
   * @param   sfResponse $response
   */
  protected function insertConversionPageContent($response)
  {
    $tracker = $this->getTrackerScript($this->uacct, $this->key, 'goal');
    $this->doInsert($response, $tracker, self::POSITION_TOP);
  }
  
}
