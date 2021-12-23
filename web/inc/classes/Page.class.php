<?php


/**
 * Page is the Controller Class
 * which should be extended by any page
 *
 * @author Christian Haschek
 */
class Page
{
    protected $_controller;
    protected $_action;
    protected $_template;
    public $variables;
    public $params;
    public $render;
    public $menu_text;
    public $menu_image;
    public $menu_priority;
    public $submenu;

    function __construct($controller, $action, $r = 1, $params = null)
    {
        $this->_controller = $controller;
        $this->_action = $action;
        $this->render = $r;
        $this->submenu = array();
        $this->setMenu();
        $this->params = $params;
        $this->menu_image = '/css/imgs/empty.png';
    }

    function setMenu()
    {
        $this->menu_text = '#';
        $this->menu_priority = 1;
    }

    /**
     * override this function to check if a user can use this object
     * @return true -> user will be able to access
     * @return false -> user will not be able to access and this page won't
     * be shown in the menu
     *  
     */
    public function maySeeThisPage()
    {
        return true;
    }

    function set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    function addSubmenuItem($text, $action)
    {
        $active = $GLOBALS['url'][0];
        if (!$active) $active = 'index';


        if ($active == $action)
            $act = 1;
        else $act = 0;

        $this->submenu[] = array('text' => $text, 'action' => $action, 'active' => $act);
    }

    function submenu()
    {
    }

    function getSubmenu($active = 0)
    {
        $prepare = array();
        if (!$_SESSION['user']) return $prepare;
        if (is_array($this->submenu))
            foreach ($this->submenu as $key => $var)
                $prepare[] = array('text' => $var['text'], 'base' => strtolower($this->_controller), 'action' => $var['action'], 'active' => $var['active']);
        return $prepare;
    }

    function __destruct()
    {
        //		if($this->render)
        //			$this->_template->render();
    }

    function getContent($vars = null)
    {
        //            return $this->_template->renderToString($vars);
    }

    function render()
    {
        $m = new Mustache_Engine(array('entity_flags' => ENT_QUOTES));

        //helpers
        $m->addHelper('case', [
            'lower' => function($value) { return strtolower((string) $value); },
            'upper' => function($value) { return strtoupper((string) $value); },
        ]);
        $m->addHelper('!!', function($value) { return $value . '!!'; });

        //custom js and css
        if (file_exists(ROOT . DS . 'pages' . DS . $this->_controller . DS . $this->_controller . '.js'))
            $this->variables['customjs'].= file_get_contents(ROOT . DS . 'pages' . DS . $this->_controller . DS . $this->_controller . '.js');
        if (file_exists(ROOT . DS . 'pages' . DS . $this->_controller . DS . $this->_controller . '.css'))
            $this->variables['customcss'] = file_get_contents(ROOT . DS . 'pages' . DS . $this->_controller . DS . $this->_controller . '.css');

        $this->variables['lang'] = ($_SESSION['lang']?$_SESSION['lang']:getLang());
        $this->variables['menu'] = getMenu();
        $this->variables['content_menu'] = ''; //@todo: render content menu if we need such a thing  //$html->content_menu($this->content_menu);
        $this->variables['FOOTER_BUGS_AND_WISHES'] = $GLOBALS['translations']['FOOTER_BUGS_AND_WISHES'];
        $this->variables['PRIVACY_STATEMENT'] = $GLOBALS['translations']['PRIVACY_STATEMENT'];

        

        if($this->variables['template'] && file_exists(ROOT . DS . 'pages' . DS . $this->_controller . DS . $this->variables['template']))
        {
            $template = file_get_contents(ROOT . DS . 'pages' . DS . $this->_controller . DS . $this->variables['template']);
            $pagecontent = $m->render($template, $this->variables);
            $this->variables['pagecontent'] = $pagecontent;
        }
        else
            $this->variables['pagecontent'] = $this->variables['content'];
        
        $template = file_get_contents(ROOT.DS.'views'.DS.'mainpage.hbs');
        echo $m->render($template, $this->variables);
    }
}
