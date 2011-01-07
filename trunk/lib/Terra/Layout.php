<?php
/**
 * Terra Duo Layout
 *
 * Provides a framework for handling layouts their CSS, JavaScripts,
 * conditional statements, navigation links and layout variables.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 0.2
 * @package Terra
 * @copyright Copyright (c) 2008-2010 Bruno De Barros.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class Terra_Layout {

    protected $LayoutFile = '';
    protected $Disabled = false;
    protected $TemplateVars = array();
    protected $NavigationLinks = array();
    protected $Stylesheets = array();
    protected $Javascripts = array();
    protected $BottomJavascripts = array();
    protected $IeIncludes = array();
    protected $BaseURL = '';
    protected $StartTime = 0;
    protected $Started = false;

    public function  __set($name,  $value) {
        $this->TemplateVars[$name] = $value;
    }

    public function  __isset($name) {
        return (isset($this->TemplateVars[$name]));
    }

    public function  __get($name) {
        return (isset($this->TemplateVars[$name])) ? $this->TemplateVars[$name] : false;
    }

    public function  __call($name,  $arguments) {
        return (isset($this->TemplateVars[$name])) ? Terra_Events::CallBack($this->TemplateVars[$name], $arguments) : false;
    }

    function __construct($File = null) {
        if ($File !== null) {
            $this->setLayoutFile($File);
        }
    }

    public function setLayoutFile($File) {
        if (!file_exists($File)) {
            throw new Terra_Exception('The Layout File you are trying to set does not exist!');
            return;
        } else {
            $this->LayoutFile = $File;
        }
    }

    public function disableLayout() {
        $this->Disabled = true;
    }

    public function enableLayout() {
        $this->Disabled = false;
    }

    public function start() {
        ob_start();
        $this->Started = true;
    }

    public function setBaseURL($BaseURL) {
        $this->BaseURL = $BaseURL;
    }

    public function TopIncludes() {

        $return = '';

        if (count($this->Stylesheets > 0)) {
            foreach($this->Stylesheets as $stylesheet) {
                $return .= '<link type="text/css" href="'.$stylesheet.'" rel="stylesheet" />';
            }
        }
        if (count($this->Javascripts > 0)) {
            foreach($this->Javascripts as $javascript) {
                $return .= '<script type="text/javascript" src="'.$javascript.'"></script>';
            }
        }
        if (count($this->IeIncludes > 0)) {
            foreach ($this->IeIncludes as $lte => $versionArray) {
                foreach($versionArray as $version => $javascriptArray) {
                    $return .= '<!--[if '.$lte.' IE '.$version.']>';
                    foreach($javascriptArray as $js) {
                        if ($js['JS']) {
                            $return .= '<script type="text/javascript" src="'.$js['INCLUDE'].'"></script>';
                        } else {
                            $return = '<link type="text/css" href="'.$js['INCLUDE'].'" rel="stylesheet" />';
                        }
                    }
                    $return .= "<![endif]-->";
                }
            }
        }

        return $return;
    }

    function addNavigationLink($href, $innerHTML, $title = null) {
        $this->NavigationLinks[] = array(
                'href' => $href,
                'innerHTML' => $innerHTML,
                'title' => ($title == null) ? $innerHTML : $title
        );
    }

    public function BottomIncludes() {
        
    }

    public function end($page = 'index', $contents = null) {
        if ($this->Started) {
            $contents = ob_get_contents();
            ob_end_clean();
        }
        include($this->LayoutFile);
    }

    function addStylesheet($StylesheetUrl) {
        $this->Stylesheets[] = $StylesheetUrl;
    }

    function addJavascript($JavascriptUrl) {
        $this->Javascripts[] = $JavascriptUrl;
    }

    function addBottomJavascript($BottomJavascriptUrl) {
        $this->BottomJavascripts[] = $BottomJavascriptUrl;
    }

    function addIeJs($IeInclude, $lte = 'lte', $version = 6) {
        $this->IeIncludes[$lte][$version][] = array(
                'JS' => true,
                'INCLUDE' => $IeInclude
        );
    }

    function getTimeSinceStart($DecimalPlaces = 3) {
        return round(microtime() - $this->StartTime, $DecimalPlaces);
    }

    function setStartTime($Timestamp) {
        $this->StartTime = $Timestamp;
    }

}