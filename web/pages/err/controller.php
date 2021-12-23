<?php

/*
 * Error handler
 */

class Err extends Page
{
    function setMenu()
    {
        $this->menu_text = '';
        $this->menu_priority = 9000;
    }

    function index()
    {
        $this->set('content', "");
    }

    public function servererror()
    {
        $this->set('title', translate('ERROR_500_TITLE'));
        $this->set('content_header', '<h2>' . translate('ERROR_500_TITLE') . '</h2>');
        $this->set('content', translate('ERROR_500_TEXT'));
    }

    public function notfound()
    {
        global $url;
        $this->set('title', translate('ERROR_404_TITLE'));
        $this->set('content_header', 'Error <strong class="red">404</strong> - ' . translate('ERROR_404_TEXT'));
        $this->set('content', translate('ERROR_404_HEADER'));
    }

    public function notallowed()
    {
        $this->set('title', translate('ERROR_403_TITLE'));
        $this->set('content_header', 'Error <strong class="red">403</strong> - ' . translate('ERROR_403_TITLE'));
        $this->set('content', translate('ERROR_403_TEXT'));
    }

    function maySeeThisPage()
    {
        return true;
    }
}
