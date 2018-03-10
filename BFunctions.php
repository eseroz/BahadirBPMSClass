<?php

require_once 'BTypes.php';
require_once 'BEncoding.php';

class BFunctions
{
    public $encoding;
    function __construct()  {
        $this->encoding = new BEncoding();
    }

    public function GET_EXCHANGE_RATE($KUR_TIPI, $ACIKLAMAYI_OKU = 'EĞER PARA YURTDIŞI BANKA HESABI İLE TRANSFER İSE "ForexSelling" VE "ForexBuying" ALANLARI KULLANILACAK, YURT İÇİNDE BANKNOT OLARAK ALINACAK VEYA VERİLECEK İSE "BanknoteSelling" VE "BanknoteBuying" ALANLARI KULLANILACAK'){

        $DOVIZ_KURU = array();
        $adres = "http://www.tcmb.gov.tr/kurlar/today.xml";
        $xml_data = simplexml_load_file($adres);
        foreach($xml_data->Currency as $Currency){
            if($Currency['Kod'] == $KUR_TIPI){
                $ForexSelling = (string)$Currency->ForexSelling;
                $ForexBuying = (string)$Currency->ForexBuying;
                //$BanknoteSelling = (string)$Currency->BanknoteSelling;
                //$BanknoteBuying = (string)$Currency->BanknoteBuying;
                $DOVIZ_KURU = array('TIP'=>$KUR_TIPI,'SATIS'=> $ForexSelling,'ALIS'=>$ForexBuying);
            }
        }
        return $DOVIZ_KURU;
    }

    public function post($par, $encode = false){
        if($encode){
            return $this->encoding->STR_UTF8_TO_MSSQL($_POST[$par]);
        }else{
            return $_POST[$par];
        }
    }

    public function get($par, $st=false){
        if($st){
            return htmlspecialchars(addslashes(trim(htmlentities($_GET[$par]))));
        }else{
            return addslashes(trim(htmlentities($_GET[$par])));
        }
    }

    public function CONVERT_POSTED_FILE_TO_BINARY($POSTED_FILE){
        if ( 0 < $POSTED_FILE['error'] ) {
            return 'Error: ' . $POSTED_FILE['error'] . '<br>';
        } else {

            $FILE_NAME = $POSTED_FILE['name'];
            $TMP_NAME  = $POSTED_FILE['tmp_name'];
            $FILE_SIZE = $POSTED_FILE['size'];
            $FILE_TYPE = $POSTED_FILE['type'];
            $DATA_STRING = file_get_contents($TMP_NAME);
            $DATA = unpack("H*hex", $DATA_STRING);
            $BINARY = "0x".$DATA['hex'];
            return $BINARY;
        }
    }

    public function IN_ARRAY_R($Aranan, $Array, $strict = false) {
        foreach ($Array as $item) {
            if (($strict ? $item === $Aranan : $item == $Aranan) || (is_array($item) && $this->in_array_r($Aranan, $item, $strict))) {
                return true;
            }
        }
        return false;
    }

    public function unsetValue(array $array, $value, $strict = TRUE)
    {
        if(($key = array_search($value, $array, $strict)) !== FALSE) {
            unset($array[$key]);
        }
        return $array;
    }

    public function TurkceKarakterTemizle($tr1){
        $turkce = array("ş","Ş","ı","ü","Ü","ö","Ö","ç","Ç","ş","Ş","ı","ğ","Ğ","İ","ö","Ö","Ç","ç","ü","Ü");
        $duzgun = array("s","S","i","u","U","o","O","c","C","s","S","i","g","G","I","o","O","C","c","u","U");
        $tr1 = str_replace($turkce, $duzgun, $tr1);
        return $tr1;
    }

    public function GET_RANDOM_GUID(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12);
            return $uuid;
        }
    }

    public function GET_SESSION_GUID(){
        if(empty($_SESSION["GUID"])){
            $_SESSION["GUID"] = $this->GET_RANDOM_GUID();
        }
        return $_SESSION["GUID"];
    }

    public function DESTROY_SESSIONS(){
        session_destroy();
        ob_end_clean();
        ob_end_flush();
    }

    public function GET_DATE_MSSQL_TYPE($MYDATE){
        $timezone = new DateTimeZone("UTC");
        $date = new DateTime($MYDATE, $timezone);
        return $date->format(DateTime::ISO8601);
    }

    function GET_CLIENT_IP() {
        $IP_ADRESS = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $IP_ADRESS = $_SERVER['HTTP_CLIENT_IP'];
        else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $IP_ADRESS = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_X_FORWARDED']))
            $IP_ADRESS = $_SERVER['HTTP_X_FORWARDED'];
        else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
            $IP_ADRESS = $_SERVER['HTTP_FORWARDED_FOR'];
        else if(isset($_SERVER['HTTP_FORWARDED']))
            $IP_ADRESS = $_SERVER['HTTP_FORWARDED'];
        else if(isset($_SERVER['REMOTE_ADDR']))
            $IP_ADRESS = $_SERVER['REMOTE_ADDR'];
        else
            $IP_ADRESS = '0.0.0.0';
        return $IP_ADRESS;
    }

    public function CONVERT_MINUTE_TO_TIMETEXT($minute){

        $secs = $minute * 60;

        $GUN = "";
        $SAAT = "";
        $DAKIKA = "";

        if($secs >= 86400) {
            $GUN = floor($secs/86400);
        }
        if($secs >= 3600){
            $SAAT = floor($secs/3600);
        }
        if($secs >= 60){
            $DAKIKA = floor($secs/60);
        }

        return $SAAT. " saat";
        //return array('GUN'=>$GUN, 'SAAT'=>$SAAT, 'DAKIKA'=>$DAKIKA);
    }

    public function CONVERT_TIME_TO_MINUTE($DAY, $HOUR, $MINUTE){
        $DAKIKA = 0;

        if($DAY > 0){
            $DAKIKA = $DAKIKA + ($DAY *24 *60);
        }

        if($HOUR > 0)
        {
            $DAKIKA = $DAKIKA + ($HOUR *60);
        }

        if($MINUTE > 0){
            $DAKIKA = $DAKIKA + $MINUTE;
        }

        return $DAKIKA;
    }
    
    public function __json_encode( $data ) {            
    if( is_array($data) || is_object($data) ) { 
        $islist = is_array($data) && ( empty($data) || array_keys($data) === range(0,count($data)-1) ); 
        
        if( $islist ) { 
            $json = '[' . implode(',', array_map('__json_encode', $data) ) . ']'; 
        } else { 
            $items = Array(); 
            foreach( $data as $key => $value ) { 
                $items[] = __json_encode("$key") . ':' . __json_encode($value); 
            } 
            $json = '{' . implode(',', $items) . '}'; 
        } 
    } elseif( is_string($data) ) { 
        # Escape non-printable or Non-ASCII characters. 
        # I also put the \\ character first, as suggested in comments on the 'addclashes' page. 
        $string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"'; 
        $json    = ''; 
        $len    = strlen($string); 
        # Convert UTF-8 to Hexadecimal Codepoints. 
        for( $i = 0; $i < $len; $i++ ) { 
            
            $char = $string[$i]; 
            $c1 = ord($char); 
            
            # Single byte; 
            if( $c1 <128 ) { 
                $json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1); 
                continue; 
            } 
            
            # Double byte 
            $c2 = ord($string[++$i]); 
            if ( ($c1 & 32) === 0 ) { 
                $json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128); 
                continue; 
            } 
            
            # Triple 
            $c3 = ord($string[++$i]); 
            if( ($c1 & 16) === 0 ) { 
                $json .= sprintf("\\u%04x", (($c1 - 224) <<12) + (($c2 - 128) << 6) + ($c3 - 128)); 
                continue; 
            } 
                
            # Quadruple 
            $c4 = ord($string[++$i]); 
            if( ($c1 & 8 ) === 0 ) { 
                $u = (($c1 & 15) << 2) + (($c2>>4) & 3) - 1; 
            
                $w1 = (54<<10) + ($u<<6) + (($c2 & 15) << 2) + (($c3>>4) & 3); 
                $w2 = (55<<10) + (($c3 & 15)<<6) + ($c4-128); 
                $json .= sprintf("\\u%04x\\u%04x", $w1, $w2); 
            } 
        } 
    } else { 
        # int, floats, bools, null 
        $json = strtolower(var_export( $data, true )); 
    } 
    return $json; 
} 
}