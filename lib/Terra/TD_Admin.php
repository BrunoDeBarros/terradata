<?php
/**
 * Terra Duo Administration Control Panel Generator
 *
 * Generates an administration control panel controller and its actions.
 * Includes user authentication and permissions.
 *
 * @author Bruno De Barros <bruno@terraduo.com>
 * @version 2010.1.27
 *
 * @copyright Copyright (c) 2008-2010 Bruno De Barros
 *
 * The MIT License
 *
 * Copyright (c) 2008-2010 Bruno De Barros
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class TD_Admin {

    protected $AdminTitle = "Administration Control Panel - Powered by Terra Duo Admin";
    protected $AdminFooter = "This Administration Control Panel is powered by <a href='http://terraduo.com/projects/admin'>Terra Duo Admin</a>";
    protected $LayoutFile;
    protected $LoginFormFile;
    /**
     * The Scaffolding
     * @var TD_Table_Scaffolding
     */
    protected $Scaffolding;
    protected $LoginRequired = false;
    protected $Stylesheets = array();
    protected $JavaScripts = array();
    protected $IeIncludes = array();
    protected $NavigationLinks = array();
    protected $LoginLink = '';
    protected $LogoutLink = '';
    protected $ShowExtraInfo = false;
    protected $HomeLink = '';
    /**
     * The Users Handler for TD_Admin.
     * @var TD_Admin_User_handler
     */
    protected $UsersHandler;

    function setAdminTitle($AdminTitle) {
        $this->AdminTitle = $AdminTitle;
    }

    function setLoginFormFile($LoginFormFile) {
        $this->LoginFormFile = $LoginFormFile;
    }

    public function __construct() {
        ob_start();
    }

    function setLoginRequired($LoginRequired) {
        $this->LoginRequired = $LoginRequired;
    }

    function setShowExtraInfo($ShowExtraInfo) {
        $this->ShowExtraInfo = $ShowExtraInfo;
    }

    function setLoginLink($LoginLink) {
        $this->LoginLink = $LoginLink;
    }

    function setLogoutLink($LogoutLink) {
        $this->LogoutLink = $LogoutLink;
    }

    function setHomeLink($HomeLink) {
        $this->HomeLink = $HomeLink;
    }

    function setUsersHandler($UsersHandler) {
        if ($UsersHandler instanceof TD_Admin_User_Handler) {
            $this->UsersHandler = $UsersHandler;
        } else {
            trigger_error('The defined users handler (class: '.get_class($UsersHandler).') does not implement the TD_Admin_User_Handler interface.', E_USER_ERROR);
            return;
        }
    }

    function addNavigationLink($href, $innerHTML, $title = null) {
        $this->NavigationLinks[] = array(
                'href' => $href,
                'innerHTML' => $innerHTML,
                'title' => ($title == null) ? $innerHTML : $title
        );
    }

    function setAdminFooter($AdminFooter) {
        $this->AdminFooter = $AdminFooter;
    }

    function setLayoutFile($File) {
        $this->LayoutFile = $File;
    }

    function setScaffolding($Scaffolding) {
        $this->Scaffolding = $Scaffolding;
    }

    function addStylesheet($StylesheetUrl) {
        $this->Stylesheets[] = $StylesheetUrl;
    }

    function addJavaScript($JavaScriptUrl) {
        $this->JavaScripts[] = $JavaScriptUrl;
    }

    function addIeInclude($IeInclude, $lte = 'lte', $version = 6, $js = true) {
        $this->IeIncludes[$lte][$version][] = array(
            'JS' => $js,
            'INCLUDE' => $IeInclude
        );
    }

    function login() {
        if (!$this->LoginRequired) {
            header('Location: '.$this->HomeLink);
            return;
        }
        if ($this->UsersHandler->isLoggedIn()) {
            $this->Scaffolding->setSuccessMessage('You are already logged in, '.$this->UsersHandler->getUsername().'.');
            header('Location: '.$this->HomeLink);
            return;
        }
        if (isset($_POST['USERNAME'])) {
            $remember =  (isset($_POST['REMEMBER_ME']) and $_POST['REMEMBER_ME'] == 1) ? true : false;
            if ($this->UsersHandler->login($_POST['USERNAME'], $_POST['PASSWORD'])) {
                $this->Scaffolding->setSuccessMessage('Welcome, '.$this->UsersHandler->getUsername().'! You have logged in successfully.');
                header('Location: '.$this->HomeLink);
                return;
            } else {
                $this->Scaffolding->setErrorMessage('Invalid username/password combination.');
                include $this->LoginFormFile;
            }
        } else {
            include $this->LoginFormFile;
        }
        $this->end('login');
    }

    function execute($callback) {
        $this->Scaffolding->TableManager->callback($callback);
        $this->end($callback);
    }

    function logout() {
        if (!$this->LoginRequired) {
            header('Location: '.$this->HomeLink);
            return;
        }
        if (!$this->UsersHandler->isLoggedIn()) {
            $this->Scaffolding->setErrorMessage('You can\'t logout, because you are not logged in.');
            header('Location: '.$this->LoginLink);
            return;
        }

        if ($this->UsersHandler->logout()) {
            $this->Scaffolding->setSuccessMessage('You have logged out successfully.');
            header('Location: '.$this->LoginLink);
        } else {
            $this->Scaffolding->setErrorMessage('A problem occured while attempting to log you out. Please try again.');
            header('Location: '.$this->HomeLink);
        }
    }

    function DeleteController($ID = null) {
        if ($this->LoginRequired and !$this->UsersHandler->isLoggedIn()) {
            header('Location: '.$this->LoginLink);
            return;
        }
        $this->Scaffolding->DeleteController($ID);
        $this->end();
    }

    function RestoreController($ID = null) {
        if ($this->LoginRequired and !$this->UsersHandler->isLoggedIn()) {
            header('Location: '.$this->LoginLink);
            return;
        }
        $this->Scaffolding->RestoreController($ID);
        $this->end();
    }

    function CreateController() {
        if ($this->LoginRequired and !$this->UsersHandler->isLoggedIn()) {
            header('Location: '.$this->LoginLink);
            return;
        }
        $this->Scaffolding->CreateController();
        $this->end();
    }

    function EditController($ID = null) {
        if ($this->LoginRequired and !$this->UsersHandler->isLoggedIn()) {
            header('Location: '.$this->LoginLink);
            return;
        }
        $this->Scaffolding->EditController($ID);
        $this->end();
    }

    function ManageController($page = 1, $rows = 10) {
        if ($this->LoginRequired and !$this->UsersHandler->isLoggedIn()) {
            header('Location: '.$this->LoginLink);
            return;
        }
        $this->Scaffolding->ManageController($page, $rows);
        $this->end();
    }

    function index() {
        if ($this->LoginRequired and !$this->UsersHandler->isLoggedIn()) {
            header('Location: '.$this->LoginLink);
            return;
        }
        print "Welcome! This is the ".$this->AdminTitle.". Click around!";
        $this->end();
    }

    protected function end($PageName = 'index') {
        $contents = ob_get_contents();
        $title = $this->AdminTitle;
        $ShowExtraInfo = $this->ShowExtraInfo;
        $loggedIn = $this->UsersHandler->isLoggedIn();
        $username = $this->UsersHandler->getUsername();
        $success = $this->Scaffolding->getSuccessMessage();
        $error = $this->Scaffolding->getErrorMessage();
        $loginLink = $this->LoginLink;
        $logoutLink = $this->LogoutLink;
        $homeLink = $this->HomeLink;
        $navigation_links = $this->NavigationLinks;
        $stylesheets = $this->Stylesheets;
        $javascripts = $this->JavaScripts;
        $ieIncludes = $this->IeIncludes;
        $footer = $this->AdminFooter;
        $baseURL = $this->BaseUrl;
        ob_end_clean();
        if (!empty($this->LayoutFile)) {
            include($this->LayoutFile);
        } else {
            print $contents;
        }
    }

    function __call($name, $arguments) {

    }
}