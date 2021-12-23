<?php

mb_internal_encoding("UTF-8");
spl_autoload_register('autoload');
//set to neurtal timezone
date_default_timezone_set('UTC');
ini_set("log_errors", 1);
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', dirname(__FILE__).DS.'..'); //path is the root web path
ini_set("error_log", ROOT . DS . '..'.DS . 'log' . DS . 'php-error.log');
define('DOMAIN',$_SERVER['REQUEST_URI']);
define('CONN', (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == "") ? 'http' : 'https');

function autoload($className)
{
    //one of the global classes?
    if (file_exists(ROOT . DS . 'inc'. DS. 'classes' . DS . $className . '.class.php'))
        require_once(ROOT . DS . 'inc'. DS. 'classes' . DS . $className . '.class.php');
    else if (file_exists(ROOT . DS . 'pages' . DS . strtolower($className) . DS . 'controller.php'))
        require_once(ROOT . DS . 'pages' . DS . strtolower($className) . DS . 'controller.php');
}

function includeManagement()
{
    require_once(ROOT.DS.'inc'.DS.'helpers.php');
    require_once(ROOT.DS.'inc'.DS.'config.inc.php');

    if(file_exists(ROOT.DS.'..'.DS.'libs'.DS.'vendor'.DS.'autoload.php'))
        require_once(ROOT.DS.'..'.DS.'libs'.DS.'vendor'.DS.'autoload.php');
}

function callHook()
{
    global $url;
    $queryString = array();

    if (!$url[0]) {
        $component = 'home';
        $action = 'index';
    } else {
        $urlArray = $url;
        $component = $urlArray[0];
        array_shift($urlArray);
        $params = $urlArray;
        if (isset($urlArray[0])) {
            $action = $urlArray[0];
            array_shift($urlArray);
        } else
            $action = 'index'; // Default Action
        $queryString = $urlArray;
    }

    if (!file_exists(ROOT . DS . 'pages' . DS . $component . DS . 'controller.php')) {
        $component = 'err';
        $action = 'notfound';
        $queryString = array('error' => $url);
    }


    $componentName = ucfirst($component);

    $dispatch = new $componentName($component, $action, false);

    if (!$dispatch->maySeeThisPage()) {
        $componentName = 'err';
        $action = 'notallowed';
        $dispatch = new $componentName('error', $action, true);
    } else
        $dispatch = new $componentName($component, $action, true, $queryString);

    if (method_exists($componentName, $action)) {
        call_user_func_array(array($dispatch, $action), $queryString);
    } else if (method_exists($componentName, 'catchAll'))
        call_user_func_array(array($dispatch, 'catchAll'), array($params));

    //var_dump($dispatch);

    $dispatch->render();
}

//@todo: replace with global menu creation system
function getMenu()
{
    //first let's find all possible menu items
    $arr = array();
    if ($handle = opendir(ROOT . DS . 'pages')) {
        while (false !== ($file = readdir($handle))) {
            if (file_exists(ROOT . DS . 'pages' . DS . $file.DS.'controller.php') && class_exists($file)) {
                
                $instance = new $file($file, 'index', false);
                $instance->setMenu();

                if($instance->maySeeThisPage()===true)
                {
                    $menu_text = $instance->menu_text;
                    $menu_priority = $instance->menu_priority;

                    if($menu_text)
                    {
                        while($arr[$menu_priority])
                            $menu_priority++;
                        $arr[$menu_priority] = array('text'=>$menu_text,'image'=>$instance->menu_image, 'url'=>$file);
                    }
                }

                
                
                /*$ur = strtolower($GLOBALS['url'][1]);
                if (!$ur) $ur = 'index';
                $class = ucfirst($file);
                if (!class_exists($class)) continue;
                $page = new $class($class, '', false);
                if (!$page->maySeeThisPage()) continue;
                $page->setMenu();
                if (!$page->menu_text) continue;
                $arr[$class]['text'] = '<i class="' . $page->menu_image . ' menu_icon_mdi"></i><br/>' . $page->menu_text;
                $arr[$class]['priority'] = $page->menu_priority;
                if ($page->menu_class)
                    $arr[$class]['class'] = $page->menu_class;
                if ($ur == strtolower($class))
                    $arr[$class]['active'] = 1;
                else $arr[$class]['active'] = 0;

                //var_dump($page);
                */
            }
        }
        closedir($handle);
    }

    //sort the menu
    ksort($arr);

    $arr = array_values($arr);

    //Now render them
    $m = new Mustache_Engine(array('entity_flags' => ENT_QUOTES));
    $mtemplate = file_get_contents(ROOT.DS.'views'.DS.'menu.hbs');
    $menu = $m->render($mtemplate, ['menu'=>$arr]);

    return $menu;
}

function URLManagement()
{
    if($_GET['url'])
        $url = explode('/',ltrim(parse_url($_GET['url'], PHP_URL_PATH),'/'));
    else $url = array_filter(explode('/',ltrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),'/')));

    $vars = [];

    foreach($url as $key=>$element)
    {
        if(($pos = strpos($element,':'))!==false)
        {
            $ekey = substr($element,0,$pos);
            $eval = substr($element,$pos+1);
            $vars[$ekey] = $eval;

            unset($url[$key]);
        }
    }

    if(count($vars)>0)
        $url = array_values($url);

    $GLOBALS['vars'] = $vars;
    $GLOBALS['url'] = $url;
    return $url;
}

function loadLangFile()
{
    $lang = ($_SESSION['lang']?$_SESSION['lang']:getLang());

    $GLOBALS['translations'] = json_decode(file_get_contents(ROOT.DS.'..'.DS.'translations'.$lang.'.json'),true);
}

function getSupportedLangs()
{
    return ['de','en'];
}

function getLang()
{
    if ($_SESSION['lang']) return $_SESSION['lang'];
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
    switch ($lang) {
        case 'de':
            return 'de';
        default:
            return 'en';
    }
}