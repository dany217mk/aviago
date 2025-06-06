<?php
class Router
{
    private $routes;

    public function __construct()
    {
        require_once('./config/routes.php');
        $this->routes = $routes;
    }
    public function run(){
        $requestUri = $_SERVER['REQUEST_URI'];
        $isPageFound = false;
        foreach ($this->routes as $controller=>$availablePaths){
            foreach ($availablePaths as $path=>$rowActionWithParameters){
                if (preg_match("~$path~", $requestUri)){
                    $actionWithParameters = preg_replace("~$path~", $rowActionWithParameters, $requestUri);
                    $actionWithParameters = str_replace(SITE_ROOT, "", $actionWithParameters);
                    $actionWithParameters = ltrim($actionWithParameters,'/');
                    $actionWithParameters = explode('/', $actionWithParameters);
                    $action = array_shift($actionWithParameters);
                    $requestedController = new $controller();
                    $requestedAction = 'action' . ucfirst($action);
                    $isPageFound = true;
                    if (!method_exists($requestedController, $requestedAction)) {
                        header("Location: " . SITE_PAGE_NOT_FOUND);
                        exit;
                    }
                    call_user_func(array($requestedController, $requestedAction), $actionWithParameters);
                    break 2;
                }
            }
        }
        if (!$isPageFound) {
          header("Location: " . SITE_PAGE_NOT_FOUND);
          exit;
        }

    }
}