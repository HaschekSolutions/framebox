<?php

class Home extends Page
{
    function setMenu()
    {
        $this->menu_text = 'home';
        $this->menu_image = 'mdi-action-home';
        $this->menu_priority = 0;
    }

    function index()
    {
        $this->set('title', 'Hello');
        $this->variables['content_header'] = "boiii";
        $this->set('robo',["name"=>"Robo","age"=>"42"]);
        $this->set('content', "boiii");
        $this->set('template', "home.hbs");
    }
}