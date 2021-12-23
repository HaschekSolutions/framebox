<?php

class Demo extends Page
{
    function setMenu()
    {
        $this->menu_text = ''; //this will be shown in the menu as a name. if it's empty, the menu item will not be rendered
        $this->menu_image = 'mdi-action-home'; //the icon of the menu item
        $this->menu_priority = 1; //for sorting the menu items. lower numbers will be shown first
    }

    function index()
    {
        $this->set('template', "demo.hbs");
        $this->set('creator','Chris');
        $this->set('creationtime','some time in space');
        $this->set('favorites',[
            ['thing'=>'Green things','reason'=>'they are green'],
            ['thing'=>'Computers','reason'=>'i have a black one'],
            ['thing'=>'Water','reason'=>'I\'d litterally die without it'],
        ]);
    }

}