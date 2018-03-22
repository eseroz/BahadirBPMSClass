<?php

require_once 'BFunctions.php';


class MSSQL_Database extends PDO {

    public $mssqlCon;
    public $functions;
    public $mysqlDb;
    public $dbPERSONEL;

    //protected private $host;
    //protected private $user;
    //protected private $password;

    function __construct($host,$database,$user,$password)  {

        ini_set("odbc.defaultlrl", "10240K");
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 2000);

        $this->host = $host;
        $this->user = $user;
        $this->password = $password;

        $this->functions = new BFunctions();

        $this->mssqlCon = odbc_connect("Driver={SQL Server};Server=$host;Database=$database;","$user","$password");
        if (odbc_error())
        {
            echo odbc_errormsg($this->mssqlCon);
        }
    }

    public function Select($sql){
        $i = 0;
        $rows = array();
        $query = odbc_exec($this->mssqlCon, $sql);
        while($row = odbc_fetch_array( $query )){
            $rows[$i] = $row;
            $i++;
        }
        echo odbc_errormsg();
        $UTF_8_ROWS = $this->functions->encoding->ARR_WALK_MSSQL_TO_UTF8($rows);
        return $UTF_8_ROWS;
    }

    public function ExecQuery($sql){
        odbc_exec($this->mssqlCon, $sql);
        return odbc_errormsg();
    }

    public function IDENT_CURRENT($TABLE_NAME){
        $return = $this->Select("SELECT IDENT_CURRENT('$TABLE_NAME') AS ID");
        return $return[0]["ID"];
    }

    public function CREATE_MSSQL_NEW_CASE($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, $PARENT_MSSQL_CASE_ID, $NEW_DOCUMENT_NUMBER = null){
        $PM_CRT_IP = $this->functions->GET_CLIENT_IP();
        $this->ExecQuery("INSERT INTO CASES (PM_PROCESS_UID,PM_CASE_ID,PM_CRT_USER_UID,PM_CRT_IP,CRT_DATE,PARENT_MSSQL_CASE_ID, REQUEST_DOC_NUMBER) VALUES('$PROCESS_UID', $PM_CASE_ID,'$PM_USER_UID','$PM_CRT_IP',GETDATE(),$PARENT_MSSQL_CASE_ID,'$NEW_DOCUMENT_NUMBER')");
    }

    public function GET_MSSQL_CASE_ID($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, $CREATE_NEW_CASE_MODE, $PARENT_MSSQL_CASE_ID = 0, $NEW_DOCUMENT_NUMBER = null)
    {
        $MSSQL_CASE_ID = 0;
        $SELECT = $this->Select("SELECT *FROM CASES WHERE PM_PROCESS_UID = '$PROCESS_UID' AND PM_CASE_ID = $PM_CASE_ID");
        if(count($SELECT) == 0){
            if($CREATE_NEW_CASE_MODE == TRUE){
                $this->CREATE_MSSQL_NEW_CASE($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, $PARENT_MSSQL_CASE_ID, $NEW_DOCUMENT_NUMBER);
                $MSSQL_CASE_ID = $this->GET_MSSQL_CASE_ID($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, $CREATE_NEW_CASE_MODE, $PARENT_MSSQL_CASE_ID, $NEW_DOCUMENT_NUMBER);
            }
        }else{
            $MSSQL_CASE_ID = $SELECT[0]["ID"];
        }
        return $MSSQL_CASE_ID;
    }

    public function GET_MSSQL_CASE_ID_AND_DOC_NUMBER($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, $SAYAC_ADI, $CREATE_NEW_CASE_MODE, $PARENT_MSSQL_CASE_ID = 0)
    {
        $SAYAC_ADI = $this->functions->encoding->STR_UTF8_TO_MSSQL($SAYAC_ADI);
        $DOKUMAN_SAYAC = $this->Select("SELECT *FROM DOKUMAN_SAYAC WHERE SAYAC_ADI ='".$SAYAC_ADI."'");
        if($DOKUMAN_SAYAC){
            $NEW_DOCUMENT_NUMBER = $this->GENERATE_DOCUMENT_NUMBER($PM_CASE_ID, $DOKUMAN_SAYAC[0]["SAYAC_HARF"]);
            $this->ExecQuery("UPDATE DOKUMAN_SAYAC SET LAST = $PM_CASE_ID WHERE SAYAC_ADI = '$SAYAC_ADI'");
            $MSSQL_CASE_ID = $this->GET_MSSQL_CASE_ID($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, $CREATE_NEW_CASE_MODE, $PARENT_MSSQL_CASE_ID, $NEW_DOCUMENT_NUMBER);
            $SONUC = array('DOC_NUMBER'=>$NEW_DOCUMENT_NUMBER, 'MSSQL_CASE_ID'=>$MSSQL_CASE_ID);
        } else {
            $SONUC = array('DOC_NUMBER'=>null, 'MSSQL_CASE_ID'=>null);
        }
        return $SONUC;
    }

    public function GET_SPECIAL_PRODUCT_NUMBER($PM_CASE_ID, $SAYAC_ADI, $SATIR_NO = null){

        $SAYAC_ADI = $this->functions->encoding->STR_UTF8_TO_MSSQL($SAYAC_ADI);
        $DOKUMAN_SAYAC = $this->Select("SELECT *FROM DOKUMAN_SAYAC WHERE SAYAC_ADI ='".$SAYAC_ADI."'");
        if($DOKUMAN_SAYAC){
            $SONUC = $this->GENERATE_DOCUMENT_NUMBER($PM_CASE_ID, $DOKUMAN_SAYAC[0]["SAYAC_HARF"],$SATIR_NO );
        } else {
            $SONUC = NULL;
        }
        return $SONUC;
    }

    public function UPDATE_SPECIAL_SUB_PRODUCT_NUMBER($SAYAC_ADI){
        $this->ExecQuery("UPDATE DOKUMAN_SAYAC SET LAST = LAST + 1 WHERE SAYAC_ADI = '".$SAYAC_ADI."'");
    }

    public function GET_SPECIAL_SUB_PRODUCT_NUMBER($PM_CASE_ID, $SAYAC_ADI){
        $SAYAC_ADI = $this->functions->encoding->STR_UTF8_TO_MSSQL($SAYAC_ADI);
        $DOKUMAN_SAYAC = $this->Select("SELECT *FROM DOKUMAN_SAYAC WHERE SAYAC_ADI ='".$SAYAC_ADI."'");

        if($DOKUMAN_SAYAC){
            $SAYAC_NO = $DOKUMAN_SAYAC[0]["SAYAC"] + 1;
            //$this->ExecQuery("UPDATE DOKUMAN_SAYAC SET SAYAC = $SAYAC_NO WHERE SAYAC_ADI = '".$SAYAC_ADI."'");
            $SONUC = $this->GENERATE_DOCUMENT_NUMBER($PM_CASE_ID, $DOKUMAN_SAYAC[0]["SAYAC_HARF"], $SAYAC_NO );
        } else {
            $SONUC = NULL;
        }
        return $SONUC;
    }

    private function GENERATE_DOCUMENT_NUMBER($sayi, $kod_tipi, $SATIR_NO = null){

        $kod = "";
        $yil = date("Y");
        if($sayi < 10){
            $kod = $kod_tipi . $yil . '00000' . $sayi;
        }
        else if($sayi >= 10 && $sayi < 100){
            $kod = $kod_tipi . $yil . '0000' . $sayi;
        }
        else if($sayi >= 100 && $sayi < 1000){
            $kod = $kod_tipi . $yil . '000' . $sayi;
        }
        else if($sayi >= 1000 && $sayi < 10000){
            $kod = $kod_tipi . $yil . '00' . $sayi;
        }
        else if($sayi >= 10000 && $sayi < 100000){
            $kod = $kod_tipi . $yil . '0' . $sayi;
        }
        else if($sayi >= 100000 && $sayi < 1000000){
            $kod = $kod_tipi . $yil . $sayi;
        }
        if($SATIR_NO !=null){
            $kod = $kod."-".$SATIR_NO;
        }
        return $kod;
    }

    public function INSERT_PM_ERROR_LOG($MSSQL_CASE_ID, $TASK_UID, $BLOCK_NAME, $DETAILS){
        $BLOCK_NAME = $this->functions->encoding->STR_MSSQL_TO_UTF8("windows-1254", $BLOCK_NAME);
        $DETAILS =   $this->functions->encoding->STR_MSSQL_TO_UTF8("windows-1254", $DETAILS);
        $DATE = date('d-m-Y H:m:s');
        $this->ExecQuery("INSERT INTO HATA_LOGLARI (CASE_ID,TASK_UID,DATE,BLOCK_NAME,DETAILS) VALUES($MSSQL_CASE_ID,'$TASK_UID','$DATE','$BLOCK_NAME','$DETAILS')");
    }

    public function INSERT_AUDIT_LOG($PROCESS_UID, $PM_CASE_ID, $PM_PERSONEL_UID, $TABLE_NAME, $OLD_JSON_DATA, $STATEMENT){

        $DATE = date('d-m-Y H:i:s');
        $MSSQL_CASE_ID = $this->GET_CASE_ID($PROCESS_UID, $PM_CASE_ID, TRUE);
        $MSSQL_PERSONEL_ID = $this->GET_USER_ID($PM_PERSONEL_UID);
        $RECORD_ID = $this->bahadir->mssqlDb->IDENT_CURRENT($TABLE_NAME);

        $NEW_JSON_DATA = null;
        $OLD_JSON_DATA = json_encode($OLD_JSON_DATA);

        switch ($STATEMENT)
        {
            case 'INSERT':
                $NEW_JSON_DATA = $this->GET_TABLE_ROW_JSON_BY_ID($TABLE_NAME, $RECORD_ID);
                break;
            case 'UPDATE':
                $NEW_JSON_DATA = $this->GET_TABLE_ROW_JSON_BY_ID($TABLE_NAME, $RECORD_ID);
                break;
            case 'DELETE':
                break;
        }

        try
        {
            $this->ExecQuery("INSERT INTO AUDIT_LOG (CASE_ID,PERSONEL_ID,TABLE_NAME,RECORD_ID,STATEMENT,OLD_JSON_DATA,NEW_JSON_DATA,DATE) VALUES($MSSQL_CASE_ID,$MSSQL_PERSONEL_ID,'$TABLE_NAME',$RECORD_ID,'$STATEMENT','$OLD_JSON_DATA','$NEW_JSON_DATA','$DATE')");
        }
        catch (Exception $exception)
        {
            echo $exception;
        }
    }

    public function GET_TABLE_ROW_JSON_BY_ID($TABLE_NAME, $ID){
        $result = $this->Select("SELECT *FROM $TABLE_NAME WHERE ID = $ID");
        return json_encode($result);
    }

    public function GET_USER_ID($PM_USER_UID){
        $PM_USER = $this->Select("SELECT ID FROM PM_USER WHERE PM_USER_UID = '$PM_USER_UID'");
        if(count($PM_USER) == 0){
            $this->ExecQuery("INSERT INTO PM_USER (PM_USER_UID) VALUES('$PM_USER_UID')");
            $PM_USER = $this->Select("SELECT ID FROM PM_USER WHERE PM_USER_UID = '$PM_USER_UID'");
        }
        return $PM_USER;
    }

    public function GET_REVISION_CODE($STOK_KODU){

        $select = $this->Select("SELECT TOP 1 *FROM Resim WHERE resimkodu LIKE '".$STOK_KODU."%' order by revtarihi desc");
        if(count($select) == 0){
            return "00";
        }else
        {
            $rev_kod = $select[0]["revizyon"] + 1;
            if($rev_kod < 10){
                $rev_kod = "0".$rev_kod;
            }
            return $rev_kod;
        }

    }

    public function TRANSLATE($WORD, $LANGUAGE_SHORT_NAME){

        $LANGUAGE = $this->Select("SELECT *FROM TRANSLATE_LANGUAGE WHERE SHORT_NAME = '$LANGUAGE_SHORT_NAME'");
        if($LANGUAGE){
            $WORD_VARMI = $this->Select("SELECT *FROM WORDS WHERE WORD = '$WORD'");
            if($WORD_VARMI){
                $WORD_ID = $WORD_VARMI[0]["ID"];
                $RETURN_VALUE ="";
                $LANGUAGE_ID = $LANGUAGE[0]["ID"];
                $DICTIONARY_VARMI = $this->Select("SELECT *FROM DICTIONARY WHERE WORD_ID = $WORD_ID AND LANGUAGE_ID = $LANGUAGE_ID");
                if($DICTIONARY_VARMI){
                    $RETURN_VALUE = $DICTIONARY_VARMI[0]["TRANSLATED_TEXT"];
                }else{
                    $RETURN_VALUE = $WORD;
                }
            }else{
                $this->ExecQuery("INSERT INTO TRANSLATE_WORDS (WORD) VALUES('$WORD')");
                $RETURN_VALUE = $WORD;
            }
            return $RETURN_VALUE;
        }else{
            return "TANIMSIZ DİL";
        }
    }

    public function GET_LANGUAGE_ID($USR_LANGUAGE, $PM_LANGUAGE_SHORT_NAME,$PM_LANGUAGE_LONG_NAME){
        $USR_LANGUAGE = mb_convert_encoding($USR_LANGUAGE,"utf-8","windows-1254");
        $LANGUAGE = $this->Select("SELECT *FROM TRANSLATE_LANGUAGE WHERE SHORT_NAME = '$USR_LANGUAGE'");
        if($LANGUAGE){
            $LANGUAGE_ID =  $LANGUAGE[0]["ID"];
        }else
        {
            $PM_LANGUAGE_LONG_NAME = mb_convert_encoding($PM_LANGUAGE_LONG_NAME, "utf-8", "windows-1254");
            $PM_LANGUAGE_SHORT_NAME = mb_convert_encoding($PM_LANGUAGE_SHORT_NAME, "utf-8", "windows-1254");
            $this->ExecQuery("INSERT INTO TRANSLATE_LANGUAGE (LONG_NAME, SHORT_NAME) VALUES('$PM_LANGUAGE_LONG_NAME','$PM_LANGUAGE_SHORT_NAME')");
            $LANGUAGE_ID = $this->IDENT_CURRENT("TRANSLATE_LANGUAGE");
        }
        return $LANGUAGE_ID;
    }

    public function WORD_TRANSLATE($WORD_ID, $USR_LANGUAGE_ID, $CEVRILECEK_KELIME){
        $KELIMENIN_CEVIRISI_VARMI = $this->Select("SELECT *FROM TRANSLATE_DICTIONARY WHERE WORD_ID = $WORD_ID");
        if($KELIMENIN_CEVIRISI_VARMI){
            $CEVRILMIS_KELIME = $this->functions->encoding->STR_MSSQL_TO_UTF8("utf-8", $KELIMENIN_CEVIRISI_VARMI[0]["TRANSLATED_TEXT"]);
            return $CEVRILMIS_KELIME;
        }else{
            return $CEVRILECEK_KELIME ;
        }
    }

    public function GET_WORD_ID($CEVRILECEK_KELIME){
        $CEVRILECEK_KELIME = $this->functions->encoding->STR_UTF8_TO_MSSQL($CEVRILECEK_KELIME);
        $KELIME = $this->Select("SELECT ID FROM TRANSLATE_WORDS WHERE WORD = '$CEVRILECEK_KELIME'");
        if($KELIME){
            return $KELIME[0]["ID"];
        } else {
            $this->ExecQuery("INSERT INTO TRANSLATE_WORDS (WORD) VALUES('$CEVRILECEK_KELIME')");
            $INSERTED_WORD_ID = $this->IDENT_CURRENT("TRANSLATE_WORDS");
            return $INSERTED_WORD_ID;
        }
    }

    public function GET_CASE_ROW($MSSQL_CASE_ID = null, $PM_CASE_ID = null)
    {
        $CASE_ROW = null;

        if($MSSQL_CASE_ID != null){
            $CASE_ROW = $this->Select("SELECT *FROM CASES WHERE ID = $MSSQL_CASE_ID");
        }

        if($PM_CASE_ID != null){
            $CASE_ROW = $this->Select("SELECT *FROM CASES WHERE PM_CASE_ID = $PM_CASE_ID");
        }

        return $CASE_ROW[0];
    }

    public function GET_CASE_CREATER_PM_UID($CASE_ID){
        $CASE = $this->GET_CASE_ROW($CASE_ID);
        return $CASE["PM_CRT_USER_UID"];
    }

    public function INSERT_UPLOAD_TEMP($CASE_ID,$UPLOAD_FILE, $ROW_INDEX, $TIP){
        if ( 0 < $UPLOAD_FILE['error'] ) {
            echo 'Error: ' . $UPLOAD_FILE['error'] . '<br>';
        } else {
            $FILE_NAME = $this->functions->encoding->STR_UTF8_TO_MSSQL($UPLOAD_FILE['name']);
            $TMP_NAME  = $UPLOAD_FILE['tmp_name'];
            $FILE_SIZE = $UPLOAD_FILE['size'];
            $FILE_TYPE = $UPLOAD_FILE['type'];
            $DATA_STRING = file_get_contents($TMP_NAME);
            $DATA = unpack("H*hex", $DATA_STRING);
            $BINARY = "0x".$DATA['hex'];
            $this->ExecQuery("INSERT INTO UPLOAD_TEMP (CASE_ID, FILE_CONTENT, FILE_NAME, FILE_TYPE, FILE_SIZE, ROW_INDEX, TIP) VALUES($CASE_ID,$BINARY,'$FILE_NAME','$FILE_TYPE','$FILE_SIZE','$ROW_INDEX','$TIP')");
        }
    }

    public function GET_PMUSER_MSSQL_USER_ID($PM_USER_UID){

        $bahadir = new Bahadir();

        $params = array();
        array_push($params, $PM_USER_UID);
        $USER = $bahadir->mysqlDb->select("SELECT *FROM USERS WHERE USR_UID = ?", $params)[0];
        $PM_MSSQL_USER = $this->Select("SELECT ID FROM PM_USER WHERE PM_USER_UID = '$PM_USER_UID'");

        if(count($PM_MSSQL_USER) > 0){
            $USR_FIRSTNAME = $USER["USR_FIRSTNAME"];
            $USR_LASTNAME = $USER["USR_LASTNAME"];
            $NAME_SURNAME = $this->functions->encoding->STR_UTF8_TO_MSSQL($USR_FIRSTNAME." ".$USR_LASTNAME);
            $USR_USERNAME = $this->functions->encoding->STR_UTF8_TO_MSSQL($USER["USR_USERNAME"]);
            $this->ExecQuery("INSERT INTO PM_USER (PM_USER_UID, NAME_SURNAME, USERNAME, INSERTION_DATE) VALUES('$PM_USER_UID','$NAME_SURNAME','$USR_USERNAME', GETDATE())");
        }

        $MSSQL_USER = $this->Select("SELECT ID FROM PM_USER WHERE PM_USER_UID = '$PM_USER_UID'");

        foreach ($MSSQL_USER as $value)
        {
            return $value["ID"];
        }
        return 1;
    }

    public function EXIST_MSSQL_USER_PROFILE($PM_USR_UID){
        $PM_USR_UID = $this->functions->encoding->STR_UTF8_TO_MSSQL($PM_USR_UID);
        $PM_MSSQL_USER = $this->Select("SELECT ID FROM PM_USER WHERE PM_USER_UID = '$PM_USR_UID'");
        return count($PM_MSSQL_USER) > 0 ? true : false;
    }

}

class MYSQL_Database extends PDO {

    public $mssqlDb;
    public $mysqlCon;
    public $fnc;

    function __construct($host,$database,$user,$password, $port)  {
        $this->mysqlCon = new PDO("mysql:dbname=$database;host=$host;port=$port;","$user","$password");
        $this->fnc = new BFunctions();
    }

    public function delete($table,$id,$dosya_yolu)
    {
        return $this->mysqlCon->query("DELETE FROM $table WHERE id = '$id' ");
    }

    public function select($sql, $array = array(), $fetchMode = PDO::FETCH_ASSOC){
        $sth = $this->mysqlCon->prepare($sql);
        foreach ($array as $key => $value) {
            $sth->bindValue($key + 1, $value);
        }
        $sth->execute();
        return $sth->fetchAll($fetchMode);
    }

    public function GET_PM_USER_PROFILE($PM_USR_UID = null, $PM_USR_USERNAME = null){

        if($PM_USR_UID != null){
            $params = array();
            array_push($params, $PM_USR_UID);
            $USER = $this->select("SELECT *FROM USERS WHERE USR_UID = ?", $params)[0];

            $bahadir = new Bahadir();

            $PM_USER_MSSQL_EXIST = $bahadir->mssqlDb->EXIST_MSSQL_USER_PROFILE($PM_USR_UID);

            if(!$PM_USER_MSSQL_EXIST){
                $USR_FIRSTNAME = $USER["USR_FIRSTNAME"];
                $USR_LASTNAME = $USER["USR_LASTNAME"];

                $NAME_SURNAME = $this->fnc->encoding->STR_UTF8_TO_MSSQL($USR_FIRSTNAME." ".$USR_LASTNAME);
                $USR_USERNAME = $this->fnc->encoding->STR_UTF8_TO_MSSQL($USER["USR_USERNAME"]);

                $bahadir->mssqlDb->ExecQuery("INSERT INTO PM_USER (PM_USER_UID,NAME_SURNAME,USERNAME,INSERTION_DATE) VALUES('$PM_USR_UID','$NAME_SURNAME','$USR_USERNAME', GETDATE())");
            }

            return $USER;
        }

        if($PM_USR_USERNAME != null){
            $params = array();
            array_push($params, $PM_USR_USERNAME);
            $USER = $this->select("SELECT *FROM USERS WHERE USR_USERNAME = ?", $params)[0];
            return $USER;
        }



    }

    public function UPDATE_TASK_DUE_TIME($SURE, $ZAMAN_BIRIMI, $PROCESS_UID, $TASK_UID){
      $this->mysqlCon->query("UPDATE task SET TAS_DURATION = ".$SURE.", TAS_TIMEUNIT = '".$ZAMAN_BIRIMI."' WHERE PRO_UID = '".$PROCESS_UID."' AND TAS_UID = '".$TASK_UID."'");       
    }

    public function GET_USER_GROUPS($PM_USER_UID){
        $USER_GROUPS = $this->select("SELECT *FROM group_user WHERE USR_UID = '$PM_USER_UID'");
        return $USER_GROUPS;
    }

    public function GET_GROUP_UID($GROUP_NAME){
        $GROUPS = $this->select("SELECT GRP_UID FROM groupwf WHERE GRP_TITLE = '$GROUP_NAME'");
        foreach ($GROUPS as $GROUP)
        {
        	return $GROUP["GRP_UID"];
        }
    }

    public function GET_GROUP_NAME($GROUP_UID){
        $GROUPS = $this->select("SELECT GRP_TITLE FROM groupwf WHERE GRP_UID = '$GROUP_UID'");
        foreach ($GROUPS as $GROUP)
        {
        	return $GROUP["GRP_TITLE"];
        }
    }

    public function GET_APP_DELEGATION($PROCESS_UID, $CASE_NUMBER){
        $DELEGATE = $this->select("SELECT *FROM app_delegation WHERE PRO_UID = '$PROCESS_UID' AND APP_NUMBER = '$CASE_NUMBER'");
        return $DELEGATE[0];
    }

    public function GET_APP_DEL_FINISH_TIME($PROCESS_UID, $CASE_NUMBER){
        $DELEGATES = $this->GET_APP_DELEGATION($PROCESS_UID, $CASE_NUMBER);
        foreach($DELEGATES as $DELEGATE){
           $DELEGATE["DEL_FINISH_DATE"];
        }
    }

    public function GET_APPLICATION_ROW($APP_UID)
    {
        $APPLICATION = $this->select("SELECT *FROM application WHERE APP_UID = '$APP_UID'");
        return $APPLICATION[0];
    }

}