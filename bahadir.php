<?php
//ob_start();
//session_start();
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
 
require_once 'BFunctions.php';
require_once 'BDatabase.php';
require_once 'BTriggers.php';

class Bahadir
{


    public static $instance;
    public $mysqlDb;
    public $mssqlDb;
    public $kanbanDb;

    function __construct() {

        $mssql_host = "192.168.6.76";
        $mssql_database = "BahadirBPMS";
        $mssql_uid = "sa";
        $mssql_password = "Ridahab956230";
        
        $this->mssqlDb = new MSSQL_Database($mssql_host,$mssql_database,$mssql_uid,$mssql_password);

        //$mssql_host = "192.168.6.147";
        //$mssql_database = "BAHADIR_KANBAN";
        //$mssql_uid = "sa";
        //$mssql_password = "bahadir956230**";
        //$this->kanbanDb = new KANBAN_Database($mssql_host,$mssql_database,$mssql_uid,$mssql_password);

        $mysql_host = "localhost";
        $mysql_database = "bitnami_pm";
        $mysql_uid = "root";
        $mysql_password = "0000";
        $port = "3306";
        $this->mysqlDb = new MYSQL_Database($mysql_host,$mysql_database,$mysql_uid,$mysql_password,$port);

        //$this->mssqlDb->mysqlDb = $this->mysqlDb;
        //$this->mysqlDb->mssqlDb = $this->mssqlDb;
    }

    public function &getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new Bahadir();
        }
        return self::$instance;
    }

    public function MAKE_NAVIGATION_MENU(){

        if(isset($_SESSION["USER_LOGGED"])){

            $PM_USER_UID = $_SESSION["USER_LOGGED"];
            $USER_GROUPS = $this->mysqlDb->GET_USER_GROUPS($PM_USER_UID);
            $MENU_HTML = '<script type="text/javascript">';
            $GRUP_NAME ="";
            foreach ($USER_GROUPS as $USER_GROUP)
            {
                $GRUP_NAME = $this->mysqlDb->GET_GROUP_NAME($USER_GROUP["GRP_UID"]);

                if($GRUP_NAME == "YÖNETİM PANELİ")
                {
                    $MENU_HTML .= "var pm_menu = document.getElementById('pm_menu');";
                    $MENU_HTML .= "var li = document.createElement('li');";
                    $MENU_HTML .= "li.id = 'BPMNSMENU';";
                    $MENU_HTML .= "li.innerHTML = '<a id=\"yonetim_paneli_href\" href=\"/BahadirBPMS/MANAGEMENT/default.php\" target=\"casesFrame;frameMain\"><span>Yönetim Paneli</span></a>';";
                }

            }

            $MENU_HTML .= "</script> ";

            return $MENU_HTML;
        }else
        {
            return false;
        }

    }

    public function getConnectionString($DatabaseNmae){
        switch ($DatabaseNmae)
        {
            case 'Personel':

                $mssql_host = ".";
                $mssql_database = "BahadirBPMS";
                $mssql_uid = "sa";
                $mssql_password = "0000";
                return array('HOST'=>$mssql_host,'DATABASE'=>$mssql_database,'USER'=>$mssql_uid,'PASSWORD'=>$mssql_password);
                break;
        }

    }

}

//// GRUPLAR
/// 1- ÜCRET YÖNETİMİ

