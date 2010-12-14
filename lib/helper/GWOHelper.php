<?php

/**
 * Helper function for the sfGoogleWebsiteOptimizerPlugin.
 * 
 * @package     sfGoogleWebsiteOptimizerPlugin
 * @subpackage  helper
 * @author      Kris Wallsmith <kris [dot] wallsmith [at] gmail [dot] com>
 * @version     SVN: $Id: GWOHelper.php 8334 2008-04-06 20:07:42Z Kris.Wallsmith $
 */

/**
 * Mark the beginning of a multivariate experiment section.
 * 
 * @throws  sfViewException if section name exceeds 20 characters in length
 * 
 * @param   string $sectionName
 * 
 * @return  string
 */
function gwo_section($sectionName)
{
  if (strlen($sectionName) > 20)
  {
    throw new sfViewException('Section name must not exceed 20 characters.');
  }
  
  return '<script>utmx_section("'.$sectionName.'")</script>';
}

/**
 * Close a multivariate experiment section.
 * 
 * @return  string
 */
function gwo_section_end()
{
  // valid xhtml
  return '<script>document.write(\'</nosc\'+\'ript>\')</script>';
}
