<?php

/**
 * Common logic for all experiment classes.
 * 
 * @package     sfGoogleWebsiteOptimizerPlugin
 * @subpackage  experiment
 * @author      Kris Wallsmith <kris [dot] wallsmith [at] gmail [dot] com>
 * @version     SVN: $Id: sfGWOExperiment.class.php 8354 2008-04-07 22:17:17Z Kris.Wallsmith $
 */
abstract class sfGWOExperiment
{
  const
    POSITION_TOP    = 'top',
    POSITION_BOTTOM = 'bottom';
  
  protected
    $name             = null,
    $key              = null,
    $uacct            = null,
    $parameterHolder  = null;
  
  public function __construct($name, $param)
  {
    $this->initialize($name, $param);
  }
  
  public function initialize($name, $param)
  {
    $this->name   = $name;
    $this->key    = $param['key'];
    $this->uacct  = $param['uacct'];
    
    $this->parameterHolder = new sfParameterHolder;
    $this->parameterHolder->add($param['pages'], 'pages');
  }
  
  public function getName()
  {
    return $this->name;
  }
  
  public function getKey()
  {
    return $this->key;
  }
  
  public function getUacct()
  {
    return $this->uacct;
  }
  
  public function getParameterHolder()
  {
    return $this->parameterHolder;
  }
  
  /**
   * Attempt to connect this experiment to the supplied request.
   * 
   * @param   sfRequest $request
   * 
   * @return  bool
   */
  public function connect(sfRequest $request)
  {
    $connected = null;
    foreach ($this->parameterHolder->getAll('pages') as $page => $param)
    {
      // loop through indexed arrays, interogate associative arrays
      if (is_int(key($param)))
      {
        foreach ($param as $p)
        {
          if ($this->doConnect($request, $page, $p))
          {
            $connected = $p;
            break 2;
          }
        }
      }
      elseif ($this->doConnect($request, $page, $param))
      {
        $connected = $param;
        break;
      }
    }
    
    if (is_null($connected))
    {
      // no connection
      return false;
    }
    else
    {
      // capture connected page parameters
      $this->parameterHolder->set('page', $page, 'connected');
      $this->parameterHolder->addByRef($connected, 'connected');
      
      return true;
    }
  }
  
  /**
   * Connection test logic.
   * 
   * Overload this method to customize how a connection is determined.
   * 
   * @todo    Add support for configuring a request parameter that should
   *          _not_ be present.
   * 
   * @param   sfRequest $request
   * @param   string $page
   * @param   array $param
   * 
   * @return  bool
   */
  protected function doConnect(sfRequest $request, $page, $param)
  {
    if (sfConfig::get('sf_logging_enabled'))
    {
      sfContext::getInstance()->getLogger()->info(sprintf('{%s} connect %s:%s', __CLASS__, $this->name, $page));
    }
    
    $match = true;
    foreach ($param as $key => $value)
    {
      // if value is null (~ in yml), this parameter must be absent, 
      // otherwise the value must match the request parameter
      if ((is_null($value) && $request->hasParameter($key)) ||
          $request->getParameter($key) !== $value)
      {
        $match = false;
        break;
      }
    }
    
    return $match;
  }
  
  /**
   * Insert the appropriate content for the connected page.
   * 
   * @throws  sfConfigurationException
   * 
   * @param   sfResponse $response
   */
  public function insertContent(sfResponse $response)
  {
    $page = $this->parameterHolder->get('page', null, 'connected');
    $method = 'insert'.ucwords($page).'PageContent';
    if (!method_exists($this, $method))
    {
      throw new sfConfigurationException(sprintf('No insertion method found for page type "%s".', $page));
    }
    
    $this->$method($response);
  }
  
  /**
   * Shared utility method for inserting content into the response.
   * 
   * @param   sfResponse $response
   * @param   string $content Content for insertion
   * @param   string $position
   */
  protected function doInsert(sfResponse $response, $content, $position = null)
  {
    if (is_null($position))
    {
      $position = self::POSITION_TOP;
    }
    
    // check for overload
    $method = 'doInsert'.$position;
    if (method_exists($this, $method))
    {
      call_user_func(array($this, $method), $response, $content);
    }
    else
    {
      $old = $response->getContent();
      
      switch ($position)
      {
        case self::POSITION_TOP:
        $new = preg_replace('/<body[^>]*>/i', "$0\n".$content."\n", $old, 1);
        break;
        
        case self::POSITION_BOTTOM:
        $new = str_ireplace('</body>', "\n".$content."\n</body>", $old);
        break;
      }
      
      if ($old == $new)
      {
        $new .= $content;
      }
      
      $response->setContent($new);
    }
  }
  
  /**
   * Get the GWO control script.
   * 
   * @param   string $key
   * 
   * @return  string
   */
  protected function getControlScript($key)
  {
    $script = 
<<<HTML
<!-- control script -->
<script>
function utmx_section(){}function utmx(){}
(function(){var k='%s',d=document,l=d.location,c=d.cookie;function f(n){
if(c){var i=c.indexOf(n+'=');if(i>-1){var j=c.indexOf(';',i);return c.substring(i+n.
length+1,j<0?c.length:j)}}}var x=f('__utmx'),xx=f('__utmxx'),h=l.hash;
d.write('<sc'+'ript src="'+
'http'+(l.protocol=='https:'?'s://ssl':'://www')+'.google-analytics.com'
+'/siteopt.js?v=1&utmxkey='+k+'&utmx='+(x?x:'')+'&utmxx='+(xx?xx:'')+'&utmxtime='
+new Date().valueOf()+(h?'&utmxhash='+escape(h.substr(1)):'')+
'" type="text/javascript" charset="utf-8"></sc'+'ript>')})();
</script>
<!-- control script end -->
HTML;
    
    return sprintf($script, $key);
  }
  
  /**
   * Get the GWO tracker script.
   * 
   * If sfGoogleAnalyticsPlugin is installed and enabled, those Javascript
   * vars that need to be repeated here will automatically be inserted.
   * 
   * @param   string $key
   * @param   string $uacct
   * @param   string $page
   * 
   * @return  string
   */
  protected function getTrackerScript($key, $uacct, $page)
  {
    $vars = null;
    if (class_exists('sfGoogleAnalyticsFilter') && sfConfig::get('app_google_analytics_enabled'))
    {
      // include select Google Analytics variables
      foreach (sfConfig::get('app_google_analytics_vars', array()) as $var => $value)
      {
        if ($var{0} != '_')
        {
          $var = '_'.$var;
        }
        
        if (in_array($var, array('_udn', '_uhash', '_utimeout', '_utcp')))
        {
          if (function_exists('json_encode'))
          {
            $value = json_encode($value);
          }
          else
          {
            sfLoader::loadHelpers(array('Escaping'));
            $value = '"'.esc_js($value).'"';
          }
          
          $vars .= sprintf("\n%s=%s;", $var, $value);
        }
      }
    }
    
    $script = 
<<<HTML
<!-- tracker script -->
<script>
if(typeof(urchinTracker)!='function')document.write('<sc'+'ript src="'+
'http'+(document.location.protocol=='https:'?'s://ssl':'://www')+
'.google-analytics.com/urchin.js'+'"></sc'+'ript>')
</script>
<script>
_uacct = '%s';%s
urchinTracker("/%s/%s");
</script>
<!-- tracker script end -->
HTML;
    
    return sprintf($script, $uacct, $vars, $key, $page);
  }
  
}
