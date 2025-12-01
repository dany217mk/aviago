<?php
    date_default_timezone_set('Europe/Moscow');
    define("SITE_ROOT", "/aviago");
    define("FULL_SITE_ROOT", "http://localhost" . SITE_ROOT);
    define("FILE_ROOT", "/aviago");
    define("FULL_FILE_ROOT", "D://xampp/htdocs" . FILE_ROOT);
    define("ASSETS", FULL_SITE_ROOT . "/assets");
    define("JS", ASSETS . "/js");
    define("CSS", ASSETS . "/css");
    define("IMG", ASSETS . "/img");
     define("AIRLINE_IMG", ASSETS . "/airline_img");
    define("LIBS", ASSETS . "/libs");
    define("REQUEST_URI_EXIST", SITE_ROOT . "/");
    define("SITE_NAME", 'Aviago');

    #contact_informations
    define("CONTACT_ADMIN", 'https://vk.com/...');
    define("ADMIN_EMAIL", '');


    #statuses
    define("SITE_STATUS", 'open');
    define("SITE_DATE_RELEASE", '31.05.2024');




    #pages
    define("SITE_PAGE_DEVELOPMENT", FULL_SITE_ROOT . '/report/501');
    define("SITE_PAGE_TECHNICAL", SITE_ROOT . '/report/503');
    define("SITE_PAGE_NOT_FOUND", FULL_SITE_ROOT . '/report/404');

    #database
    $db = array(
           'host' => '',
           'user' => '',
           'password' => '',
           'db_name' => 'aviago',
           'port' => '',
           'charset' => 'utf8'
       );