<?php


$f[]="\xc2\xac";  $t[]="\x80";
$f[]="\xd9\xbe";  $t[]="\x81";
$f[]="\xc0\x9a";  $t[]="\x82";
$f[]="\xc6\x92";  $t[]="\x83";
$f[]="\xc0\x9e";  $t[]="\x84";
$f[]="\xc0\xa6";  $t[]="\x85";
$f[]="\xc0\xa0";  $t[]="\x86";
$f[]="\xc0\xa1";  $t[]="\x87";
$f[]="\xcb\x86";  $t[]="\x88";
$f[]="\xc0\xb0";  $t[]="\x89";
$f[]="\xd9\xb9";  $t[]="\x8a";
$f[]="\xc0\xb9";  $t[]="\x8b";
$f[]="\xc5\x92";  $t[]="\x8c";
$f[]="\xda\x86";  $t[]="\x8d";
$f[]="\xda\x98";  $t[]="\x8e";
$f[]="\xda\x88";  $t[]="\x8f";
$f[]="\xda\xaf";  $t[]="\x90";
$f[]="\xc0\x98";  $t[]="\x91";
$f[]="\xc0\x99";  $t[]="\x92";
$f[]="\xc0\x9c";  $t[]="\x93";
$f[]="\xc0\x9d";  $t[]="\x94";
$f[]="\xc0\xa2";  $t[]="\x95";
$f[]="\xc0\x93";  $t[]="\x96";
$f[]="\xc0\x94";  $t[]="\x97";
$f[]="\xda\xa9";  $t[]="\x98";
$f[]="\xc4\xa2";  $t[]="\x99";
$f[]="\xda\x91";  $t[]="\x9a";
$f[]="\xc0\xba";  $t[]="\x9b";
$f[]="\xc5\x93";  $t[]="\x9c";
$f[]="\xc0\x8c";  $t[]="\x9d";
$f[]="\xc0\x8d";  $t[]="\x9e";
$f[]="\xda\xba";  $t[]="\x9f";
$f[]="\xd8\x8c";  $t[]="\xa1";
$f[]="\xda\xbe";  $t[]="\xaa";
$f[]="\xd8\x9b";  $t[]="\xba";
$f[]="\xd8\x9f";  $t[]="\xbf";
$f[]="\xdb\x81";  $t[]="\xc0";
$f[]="\xd8\xa1";  $t[]="\xc1";
$f[]="\xd8\xa2";  $t[]="\xc2";
$f[]="\xd8\xa3";  $t[]="\xc3";
$f[]="\xd8\xa4";  $t[]="\xc4";
$f[]="\xd8\xa5";  $t[]="\xc5";
$f[]="\xd8\xa6";  $t[]="\xc6";
$f[]="\xd8\xa7";  $t[]="\xc7";
$f[]="\xd8\xa8";  $t[]="\xc8";
$f[]="\xd8\xa9";  $t[]="\xc9";
$f[]="\xd8\xaa";  $t[]="\xca";
$f[]="\xd8\xab";  $t[]="\xcb";
$f[]="\xd8\xac";  $t[]="\xcc";
$f[]="\xd8\xad";  $t[]="\xcd";
$f[]="\xd8\xae";  $t[]="\xce";
$f[]="\xd8\xaf";  $t[]="\xcf";
$f[]="\xd8\xb0";  $t[]="\xd0";
$f[]="\xd8\xb1";  $t[]="\xd1";
$f[]="\xd8\xb2";  $t[]="\xd2";
$f[]="\xd8\xb3";  $t[]="\xd3";
$f[]="\xd8\xb4";  $t[]="\xd4";
$f[]="\xd8\xb5";  $t[]="\xd5";
$f[]="\xd8\xb6";  $t[]="\xd6";
$f[]="\xd8\xb7";  $t[]="\xd8";
$f[]="\xd8\xb8";  $t[]="\xd9";
$f[]="\xd8\xb9";  $t[]="\xda";
$f[]="\xd8\xba";  $t[]="\xdb";
$f[]="\xd9\x80";  $t[]="\xdc";
$f[]="\xd9\x81";  $t[]="\xdd";
$f[]="\xd9\x82";  $t[]="\xde";
$f[]="\xd9\x83";  $t[]="\xdf";
$f[]="\xd9\x84";  $t[]="\xe1";
$f[]="\xd9\x85";  $t[]="\xe3";
$f[]="\xd9\x86";  $t[]="\xe4";
$f[]="\xd9\x87";  $t[]="\xe5";
$f[]="\xd9\x88";  $t[]="\xe6";
$f[]="\xd9\x89";  $t[]="\xec";
$f[]="\xd9\x8a";  $t[]="\xed";
$f[]="\xd9\x8b";  $t[]="\xf0";
$f[]="\xd9\x8c";  $t[]="\xf1";
$f[]="\xd9\x8d";  $t[]="\xf2";
$f[]="\xd9\x8e";  $t[]="\xf3";
$f[]="\xd9\x8f";  $t[]="\xf5";
$f[]="\xd9\x90";  $t[]="\xf6";
$f[]="\xd9\x91";  $t[]="\xf8";
$f[]="\xd9\x92";  $t[]="\xfa";
$f[]="\xc0\x8e";  $t[]="\xfd";
$f[]="\xc0\x8f";  $t[]="\xfe";
$f[]="\xdb\x92";  $t[]="\xff";


class BEncoding
{
    public function UTF8_TO_WIN_HEX($str) {
        global $f, $t;
        return str_replace($f, $t, $str);
    }

    public function STR_MSSQL_TO_UTF8($encoding_type, $value){
        return mb_convert_encoding($value, $encoding_type, mb_detect_encoding($value, mb_detect_order(), true));
    }

    public function ARR_WALK_MSSQL_TO_UTF8($array)
    {
        foreach($array as $key => $value)
        {
            if(is_array($value))
            {
                $array[$key] = $this->ARR_WALK_MSSQL_TO_UTF8($value);
            }
            else
            {
                $array[$key] = mb_convert_encoding($this->TR_UNICODE_REPLACE($this->UTF8_TO_WIN_HEX($value)), "utf-8", "windows-1254");
            }
        }
        return $array;
    }

    public function ARR_WALK_UTF8_TO_MSSQL($array)
    {
        foreach($array as $key => $value)
        {
            if(is_array($value))
            {
                $array[$key] = $this->ARR_WALK_MSSQL_TO_UTF8($value);
            }
            else
            {
                $array[$key] = mb_convert_encoding($this->TR_UNICODE_REPLACE($this->UTF8_TO_WIN_HEX($value)), "windows-1254", "utf-8");
            }
        }
        return $array;
    }

    public function ARR_WALK_UNICODE($array)
    {
        foreach($array as $key => $value)
        {
            if(is_array($value))
            {
                $array[$key] = $this->ARR_WALK_UNICODE($value);
            }
            else
            {
                $array[$key] = mb_convert_encoding($this->TR_UNICODE_REPLACE($this->UTF8_TO_WIN_HEX($value)), "utf-8", "windows-1254");
            }
        }
        return $array;
    }

    public function STR_UTF8_TO_MSSQL($str){
        return mb_convert_encoding($str, "windows-1254","utf-8");
    }

    public function TR_UNICODE_REPLACE($string) {
        $Sorunlu = array("\u011f","\u011e","\u0131","\u0130","\u00f6","\u00d6","\u00fc","\u00dc","\u015f","\u015e","\u00e7","\u00c7");
        $Duzeltilecek = array("ğ","Ğ","ı","İ","ö","Ö","ü","Ü","ş","Ş","ç","Ç");
        return str_replace($Sorunlu, $Duzeltilecek, $string);
    }

    public function HTML_ENTITY_DECODE($html){
        return html_entity_decode(htmlspecialchars_decode($html));
    }
}