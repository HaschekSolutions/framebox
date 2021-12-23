<?php


/**
 * Simple firewall / IDS system
 */
class IDS
{
    
    function blockBadRequest()
    {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $ip = getUserIP();        
        $url = ltrim($_GET['url'], '/');
        if (strpos($agent, 'nikto') !== false || strpos($agent, 'sqlmap') !== false || startswith($url,'wp-') || startswith($url,'wordpress') || startswith($url,'wp/'))
            exit('y u do dis? :(');
    }


    function logEvent($event)
    {
        $ip = getUserIP();
        $agent = $_SERVER['HTTP_USER_AGENT'];
        global $url;
        $user = $_SESSION['user']?$_SESSION['user']:'0';
        
        addToLog("IP: $ip, url=$url, event=$event, user=$user, info=$parsedinfo, agent=$agent",'IDS');
    }
}