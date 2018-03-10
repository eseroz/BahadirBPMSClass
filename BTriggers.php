<?php
class BTriggers
{
    public function PROFORMA_OLUSTURMA_SURECI_TALEP_DEGERLENDIR_AFTER_TRIGGER($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, $PARENT_MSSQL_CASE_ID ){

        $bahadir = new Bahadir();
        $MSSQL_CASE_ID = $bahadir->mssqlDb->GET_MSSQL_CASE_ID($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, false, $PARENT_MSSQL_CASE_ID);
        $SONUC = $bahadir->mssqlDb->Select("SELECT ID,DEGERLENDIRME_SONUC FROM SIPARIS WHERE DEGERLENDIRME_CASE_ID = $MSSQL_CASE_ID");
        $SIPARIS_ID = $SONUC[0]["ID"];
        $DEGERLENDIRME_SONUC = $SONUC[0]["DEGERLENDIRME_SONUC"];
        $OZEL_URETIM_COUNT = $bahadir->mssqlDb->Select("SELECT COUNT(OZEL_URETIM) AS OZEL_URETIM_URUN_SAYISI FROM TALEP_SATIR WHERE OZEL_URETIM = 1 AND SIPARIS_ID = $SIPARIS_ID");
        $STANDART_URUN_COUNT = $bahadir->mssqlDb->Select("SELECT COUNT(OZEL_URETIM) AS STANDART_URUN_SAYISI FROM TALEP_SATIR WHERE OZEL_URETIM = 0 AND SIPARIS_ID = $SIPARIS_ID");

        $TEKNIK_RESIM_OLANLAR = 0;
        $TEKNIK_RESIM_OLMAYANLAR = 0;
        $TEKNIK_RESIM_GUNCEL_OLANLAR = 0;
        $TEKNIK_RESIM_GUNCEL_OLMAYANLAR = 0;
        $RECETESI_OLMAYAN_STOK_KART_IDLER = [];

        $CURRENT_SITUATION = 'REFERANS_TALEBI_DEGERLENDIRILIYOR';

        foreach ($bahadir->mssqlDb->Select("SELECT STOK_KART_ID FROM TALEP_SATIR WHERE SIPARIS_ID = $SIPARIS_ID AND OZEL_URETIM = 0") as $STANDART_URUN_TALEP_SATIR)
        {
            $STOK_KART_ID = $STANDART_URUN_TALEP_SATIR["STOK_KART_ID"];
            $TEKNIK_RESIMLER = $bahadir->mssqlDb->Select("SELECT ID FROM TEKNIK_RESIM WHERE STOK_KART_ID = $STOK_KART_ID");

            if(count($TEKNIK_RESIMLER) == 0){

                $TEKNIK_RESIM_OLMAYANLAR = $TEKNIK_RESIM_OLMAYANLAR + 1;                 
                $INSERT = $bahadir->mssqlDb->ExecQuery("INSERT INTO TEKNIK_RESIM (CASE_ID, STOK_KART_ID, CURRENT_SITUATION, KAYIT_TARIHI, SILINDI, GUNCEL, ISPROCESSING) VALUES($MSSQL_CASE_ID, $STOK_KART_ID, '$CURRENT_SITUATION', GETDATE(), 0, 0, 1)");
       
            }else{

                $TEKNIK_RESIM_OLANLAR = $TEKNIK_RESIM_OLANLAR + 1;
                foreach ($TEKNIK_RESIMLER as $TEKNIK_RESIM)
                {
                    if($TEKNIK_RESIM["GUNCEL"] == 1){
                        $TEKNIK_RESIM_GUNCEL_OLANLAR = $TEKNIK_RESIM_GUNCEL_OLANLAR + 1;
                    } else {
                        $TEKNIK_RESIM_GUNCEL_OLMAYANLAR = $TEKNIK_RESIM_GUNCEL_OLMAYANLAR + 1;
                    }

                    $UPDATE = $bahadir->mssqlDb->ExecQuery("UPDATE TEKNIK_RESIM SET ISPROCESSING = 1, CURRENT_SITUATION = '$CURRENT_SITUATION' WHERE STOK_KART_ID = $STOK_KART_ID");

                }

            }

            $RECETESI_OLMAYAN_COCUKLARI_GETIR = function($PARENT_STOK_KART_ID, $bahadir) use (&$RECETESI_OLMAYAN_COCUKLARI_GETIR){
                $RECETESI_OLMAYAN_STOK_KART_IDLER = [];
                $RECETE_SATIRLAR = $bahadir->mssqlDb->Select("SELECT dbo.URETIM_RECETESI.ID, dbo.URETIM_RECETESI.PARENT_ID, dbo.URETIM_RECETESI.STOK_KART_ID, dbo.URETIM_RECETESI.MALZEME_KART_ID, dbo.URETIM_RECETESI.MIKTAR, dbo.STOK_KATEGORI.KATEGORI_ADI, dbo.STOK_KART.STOK_KATEGORI_ID, dbo.STOK_STATU.ID AS STOK_STATU_ID FROM dbo.URETIM_RECETESI INNER JOIN dbo.STOK_KART ON dbo.STOK_KART.ID = dbo.URETIM_RECETESI.STOK_KART_ID INNER JOIN dbo.STOK_KATEGORI ON dbo.STOK_KART.STOK_KATEGORI_ID = dbo.STOK_KATEGORI.ID INNER JOIN dbo.STOK_STATU ON dbo.STOK_KART.STATU_ID = dbo.STOK_STATU.ID WHERE STOK_STATU.ID != 1 AND STOK_KATEGORI_ID != 3 AND STOK_KART_ID = $PARENT_STOK_KART_ID");
                if(count($RECETE_SATIRLAR) == 0){
                    $ISPROCESSING = $bahadir->mssqlDb->Select("SELECT *FROM STOK_KART WHERE ID = $PARENT_STOK_KART_ID");
                    if($ISPROCESSING[0]["ISPROCESSING"] == 0){
                        array_push($RECETESI_OLMAYAN_STOK_KART_IDLER, $PARENT_STOK_KART_ID);
                        $bahadir->mssqlDb->ExecQuery("UPDATE STOK_KART SET ISPROCESSING = 1 WHERE ID = $PARENT_STOK_KART_ID");
                    }
                }else{
                    foreach ($RECETE_SATIRLAR as $RECETE_SATIR)
                    {
                        $MALZEME_KART_ID = $RECETE_SATIR["MALZEME_KART_ID"];
                        $RECETESI_OLMAYAN_STOK_KART_IDLER = array_merge($RECETESI_OLMAYAN_STOK_KART_IDLER, $RECETESI_OLMAYAN_COCUKLARI_GETIR($MALZEME_KART_ID, $bahadir));
                    }
                }
                return $RECETESI_OLMAYAN_STOK_KART_IDLER;
            };

            $RECETE_SATIRLAR = $bahadir->mssqlDb->Select("SELECT dbo.URETIM_RECETESI.ID, dbo.URETIM_RECETESI.PARENT_ID, dbo.URETIM_RECETESI.STOK_KART_ID, dbo.URETIM_RECETESI.MALZEME_KART_ID, dbo.URETIM_RECETESI.MIKTAR, dbo.STOK_KATEGORI.KATEGORI_ADI, dbo.STOK_KART.STOK_KATEGORI_ID, dbo.STOK_STATU.ID AS STOK_STATU_ID FROM dbo.URETIM_RECETESI INNER JOIN dbo.STOK_KART ON dbo.STOK_KART.ID = dbo.URETIM_RECETESI.STOK_KART_ID INNER JOIN dbo.STOK_KATEGORI ON dbo.STOK_KART.STOK_KATEGORI_ID = dbo.STOK_KATEGORI.ID INNER JOIN dbo.STOK_STATU ON dbo.STOK_KART.STATU_ID = dbo.STOK_STATU.ID WHERE STOK_STATU.ID != 1 AND STOK_KATEGORI_ID != 3 AND STOK_KART_ID = $STOK_KART_ID");
            if(count($RECETE_SATIRLAR) == 0){
                $ISPROCESSING = $bahadir->mssqlDb->Select("SELECT ID,ISPROCESSING FROM STOK_KART WHERE ID = $STOK_KART_ID");
                if($ISPROCESSING[0]["ISPROCESSING"] == 0){
                    array_push($RECETESI_OLMAYAN_STOK_KART_IDLER, $STOK_KART_ID);
                    $bahadir->mssqlDb->ExecQuery("UPDATE STOK_KART SET ISPROCESSING = 1 WHERE ID = $STOK_KART_ID");
                }
            }else{
                foreach ($RECETE_SATIRLAR as $RECETE_SATIR)
                {
                    $MALZEME_KART_ID = $RECETE_SATIR["MALZEME_KART_ID"];
                    $RECETESI_OLMAYAN_STOK_KART_IDLER = array_merge($RECETESI_OLMAYAN_STOK_KART_IDLER, $RECETESI_OLMAYAN_COCUKLARI_GETIR($MALZEME_KART_ID, $bahadir));
                }
            }
        }

        foreach ($bahadir->mssqlDb->Select("SELECT OZEL_URETIM_KODU FROM TALEP_SATIR WHERE SIPARIS_ID = $SIPARIS_ID AND OZEL_URETIM = 1") as $OZEL_URUN_TALEP_SATIR){
            
            $OZEL_URETIM_KODU = $OZEL_URUN_TALEP_SATIR["OZEL_URETIM_KODU"];
            $INSERT = $bahadir->mssqlDb->ExecQuery("INSERT INTO TEKNIK_RESIM (CASE_ID, OZEL_URETIM_KODU, CURRENT_SITUATION, KAYIT_TARIHI, SILINDI, GUNCEL, ISPROCESSING) VALUES($MSSQL_CASE_ID, '$OZEL_URETIM_KODU', '$CURRENT_SITUATION', GETDATE(), 0, 0, 1)");       

        }

        return array(
                'DEGERLENDIRME_SONUC'=>$DEGERLENDIRME_SONUC,
                'TEKNIK_RESMI_OLANLAR'=>$TEKNIK_RESIM_OLANLAR,
                'TEKNIK_RESMI_OLMAYANLAR'=>$TEKNIK_RESIM_OLMAYANLAR,
                'TEKNIK_RESIM_GUNCEL_OLANLAR'=>$TEKNIK_RESIM_GUNCEL_OLANLAR,
                'TEKNIK_RESIM_GUNCEL_OLMAYANLAR'=>$TEKNIK_RESIM_GUNCEL_OLMAYANLAR,
                'RECETESI_OLMAYAN_URUN_SAYISI'=>count($RECETESI_OLMAYAN_STOK_KART_IDLER),
                'OZEL_URETIM_URUN_SAYISI'=>$OZEL_URETIM_COUNT[0]["OZEL_URETIM_URUN_SAYISI"],
                'STANDART_URUN_SAYISI'=>$STANDART_URUN_COUNT[0]["STANDART_URUN_SAYISI"]
                );
    }

    public function OZEL_URETIM_PROJE_BERATI_HAZIRLAMA_AFTER($PARENT_MSSQL_CASE_ID) {

        $RECETESI_OLMAYAN_URUNLER = [];
        $RECETESI_OLMAYAN_URUN_SAYISI = 0;
        $TEKNIK_RESIM_OLANLAR = 0;
        $TEKNIK_RESIM_OLMAYANLAR = 0;
        $TEKNIK_RESIM_GUNCEL_OLANLAR = 0;
        $TEKNIK_RESIM_GUNCEL_OLMAYANLAR = 0;
        $TALEP_TIPI = "";

        $bahadir = new Bahadir();
        $SIPARIS = $bahadir->mssqlDb->Select("SELECT *FROM SIPARIS WHERE CASE_ID = $PARENT_MSSQL_CASE_ID")[0];
        $SIPARIS_ID = $SIPARIS["ID"];
        $TALEP_TIPI = $SIPARIS["TALEP_TIPI"];

        foreach ($bahadir->mssqlDb->Select("SELECT *FROM TALEP_SATIR WHERE OZEL_URETIM = 1 AND SIPARIS_ID = $SIPARIS_ID") as $TALEP_SATIR)
        {
            $STOK_KART_ID = $TALEP_SATIR["STOK_KART_ID"];
            $TEKNIK_RESIMLER = $bahadir->mssqlDb->Select("SELECT *FROM TEKNIK_RESIM WHERE STOK_KART_ID = $STOK_KART_ID");

            if(count($TEKNIK_RESIMLER) == 0){
                $TEKNIK_RESIM_OLMAYANLAR = $TEKNIK_RESIM_OLMAYANLAR + 1;
            }else{
                $TEKNIK_RESIM_OLANLAR = $TEKNIK_RESIM_OLANLAR + 1;
                foreach ($TEKNIK_RESIMLER as $TEKNIK_RESIM)
                {
                    if($TEKNIK_RESIM["GUNCEL"] == 1){
                        $TEKNIK_RESIM_GUNCEL_OLANLAR = $TEKNIK_RESIM_GUNCEL_OLANLAR + 1;
                    } else {
                        $TEKNIK_RESIM_GUNCEL_OLMAYANLAR = $TEKNIK_RESIM_GUNCEL_OLMAYANLAR + 1;
                    }
                }
            }

            //foreach ($bahadir->mssqlDb->Select("SELECT dbo.URETIM_RECETESI.ID, dbo.URETIM_RECETESI.PARENT_ID, dbo.URETIM_RECETESI.STOK_KART_ID, dbo.URETIM_RECETESI.MALZEME_KART_ID, dbo.URETIM_RECETESI.MIKTAR, dbo.STOK_KATEGORI.KATEGORI_ADI, dbo.STOK_KART.STOK_KATEGORI_ID FROM dbo.URETIM_RECETESI INNER JOIN dbo.STOK_KART ON dbo.STOK_KART.ID = dbo.URETIM_RECETESI.STOK_KART_ID INNER JOIN dbo.STOK_KATEGORI ON dbo.STOK_KART.STOK_KATEGORI_ID = dbo.STOK_KATEGORI.ID WHERE STOK_KART_ID = $STOK_KART_ID") as $RECETE_SATIR)
            //{
            //    $STOK_KART_ID = $RECETE_SATIR["STOK_KART_ID"];

            //    $RECETESI_OLMAYAN_COCUKLARI_GETIR = function($PARENT_STOK_KART_ID, $bahadir) use (&$RECETESI_OLMAYAN_COCUKLARI_GETIR){
            //        $RECETESI_OLMAYAN_STOK_KART_IDLER = [];
            //        $RECETE_SATIRLAR = $bahadir->mssqlDb->Select("SELECT dbo.URETIM_RECETESI.ID, dbo.URETIM_RECETESI.PARENT_ID, dbo.URETIM_RECETESI.STOK_KART_ID, dbo.URETIM_RECETESI.MALZEME_KART_ID, dbo.URETIM_RECETESI.MIKTAR, dbo.STOK_KATEGORI.KATEGORI_ADI, dbo.STOK_KART.STOK_KATEGORI_ID, dbo.STOK_STATU.ID AS STOK_STATU_ID FROM dbo.URETIM_RECETESI INNER JOIN dbo.STOK_KART ON dbo.STOK_KART.ID = dbo.URETIM_RECETESI.STOK_KART_ID INNER JOIN dbo.STOK_KATEGORI ON dbo.STOK_KART.STOK_KATEGORI_ID = dbo.STOK_KATEGORI.ID INNER JOIN dbo.STOK_STATU ON dbo.STOK_KART.STATU_ID = dbo.STOK_STATU.ID WHERE STOK_STATU.ID != 1 AND STOK_KATEGORI_ID != 3 AND STOK_KART_ID = $PARENT_STOK_KART_ID");
            //        if(count($RECETE_SATIRLAR) == 0){
            //            $ISPROCESSING = $bahadir->mssqlDb->Select("SELECT *FROM STOK_KART WHERE ID = $PARENT_STOK_KART_ID");
            //            if($ISPROCESSING[0]["ISPROCESSING"] == 0){
            //                array_push($RECETESI_OLMAYAN_STOK_KART_IDLER, $PARENT_STOK_KART_ID);
            //                $bahadir->mssqlDb->ExecQuery("UPDATE STOK_KART SET ISPROCESSING = 1 WHERE ID = $PARENT_STOK_KART_ID");
            //            }
            //        }else{
            //            foreach ($RECETE_SATIRLAR as $RECETE_SATIR)
            //            {
            //                $MALZEME_KART_ID = $RECETE_SATIR["MALZEME_KART_ID"];
            //                $RECETESI_OLMAYAN_STOK_KART_IDLER = array_merge($RECETESI_OLMAYAN_STOK_KART_IDLER, $RECETESI_OLMAYAN_COCUKLARI_GETIR($MALZEME_KART_ID, $bahadir));
            //            }
            //        }
            //        return $RECETESI_OLMAYAN_STOK_KART_IDLER;
            //    };

            //    $RECETESI_OLMAYAN_URUNLER = array_merge($RECETESI_OLMAYAN_URUNLER, $RECETESI_OLMAYAN_COCUKLARI_GETIR($STOK_KART_ID, $RECETESI_OLMAYAN_URUNLER,  $bahadir));
            //}

            //$RECETESI_OLMAYAN_URUN_SAYISI = count($RECETESI_OLMAYAN_URUNLER);
            $RECETESI_OLMAYAN_URUN_SAYISI = 3;
        }

        return array(
                'TEKNIK_RESMI_OLANLAR'=>$TEKNIK_RESIM_OLANLAR,
                'TEKNIK_RESMI_OLMAYANLAR'=>$TEKNIK_RESIM_OLMAYANLAR,
                'TEKNIK_RESIM_GUNCEL_OLANLAR'=>$TEKNIK_RESIM_GUNCEL_OLANLAR,
                'TEKNIK_RESIM_GUNCEL_OLMAYANLAR'=>$TEKNIK_RESIM_GUNCEL_OLMAYANLAR,
                'RECETESI_OLMAYAN_URUN_SAYISI'=>$RECETESI_OLMAYAN_URUN_SAYISI,
                'RECETESI_OLMAYAN_URUNLER'=>$RECETESI_OLMAYAN_URUNLER,
                'TALEP_TIPI'=>$TALEP_TIPI
                );
    }

    public function TALEP_OLUSTUR_AFTER_TRIGGER($PARENT_MSSQL_CASE_ID){
        $bahadir = new Bahadir();
        $SONUC = $bahadir->mssqlDb->Select("SELECT *FROM SIPARIS WHERE CASE_ID = $PARENT_MSSQL_CASE_ID");
        return $SONUC[0]["TALEP_NO"];
    }

    public function REFERANS_HAZIRLAMA_SURECI_TALEP_DEGERLENDIR_AFTER($PARENT_MSSQL_CASE_ID, $REFERANS_TALEP_MSSQL_CASE_ID){

        $ATANACAK_KULLANICI_IDLERI = [];
        $bahadir = new Bahadir();


        if($REFERANS_TALEP_MSSQL_CASE_ID > 0){

            $TALEP_SATIRLARI = $bahadir->mssqlDb->Select("SELECT ID FROM TALEP_SATIR WHERE REFERANS_TALEP_CASE_ID = $REFERANS_TALEP_MSSQL_CASE_ID");
            foreach ($TALEP_SATIRLARI as $TALEP_SATIR)
            {
                $SIPARIS_SATIR_ID = $TALEP_SATIR["ID"];
            	$TERMINLER = $bahadir->mssqlDb->Select("SELECT ASSIGNED_PM_USER_UID, ISJOB FROM TERMIN WHERE SATIR_ID = $SIPARIS_SATIR_ID AND ISJOB = 1");
                foreach ($TERMINLER as $TERMIN)
                {
                    $USER_UID = $TERMIN["ASSIGNED_PM_USER_UID"];
                    $varmi = false;
                    foreach ($ATANACAK_KULLANICI_IDLERI as $UID)
                    {
                        if($UID == $USER_UID){
                            $varmi = true;
                        }
                    }

                    if($varmi == false){
                        array_push($ATANACAK_KULLANICI_IDLERI, $USER_UID);
                    }
                }
            }
            return $ATANACAK_KULLANICI_IDLERI;
        } else {
            $SIPARISLER = $bahadir->mssqlDb->Select("SELECT ID FROM SIPARIS WHERE CASE_ID = $PARENT_MSSQL_CASE_ID");
            foreach ($SIPARISLER as $SIPARIS)
            {
                $SIPARIS_ID = $SIPARIS["ID"];
                $TALEP_SATIRLARI = $bahadir->mssqlDb->Select("SELECT ID FROM TALEP_SATIR WHERE SIPARIS_ID = $SIPARIS_ID");
                foreach ($TALEP_SATIRLARI as $TALEP_SATIR)
                {
                    $SIPARIS_SATIR_ID = $TALEP_SATIR["ID"];
            	    $TERMINLER = $bahadir->mssqlDb->Select("SELECT ASSIGNED_PM_USER_UID, ISJOB FROM TERMIN WHERE SATIR_ID = $SIPARIS_SATIR_ID AND ISJOB = 1");
                    foreach ($TERMINLER as $TERMIN)
                    {
                        $USER_UID = $TERMIN["ASSIGNED_PM_USER_UID"];
                        $varmi = false;
                        foreach ($ATANACAK_KULLANICI_IDLERI as $UID)
                        {
                            if($UID == $USER_UID){
                                $varmi = true;
                            }
                        }

                        if($varmi == false){
                            array_push($ATANACAK_KULLANICI_IDLERI, $USER_UID);
                        }
                    }
                }
                return $ATANACAK_KULLANICI_IDLERI;
            }
        }
    }

    public function REFERANS_HAZIRLAMA_SURECI_TALEP_DEGERLENDIR_AFTER2($PARENT_MSSQL_CASE_ID, $REFERANS_TALEP_MSSQL_CASE_ID)
    {
        $bahadir = new Bahadir();
        $SIPARISLER = $bahadir->mssqlDb->Select("SELECT ID FROM SIPARIS WHERE CASE_ID = $PARENT_MSSQL_CASE_ID");
        $TERMIN_COUNT = 0;
        foreach ($SIPARISLER as $SIPARIS)
        {
            $SIPARIS_ID = $SIPARIS["ID"];
            $TALEP_SATIRLARI = $bahadir->mssqlDb->Select("SELECT ID FROM TALEP_SATIR WHERE SIPARIS_ID = $SIPARIS_ID");
            foreach ($TALEP_SATIRLARI as $TALEP_SATIR)
            {
                $SIPARIS_SATIR_ID = $TALEP_SATIR["ID"];
            	$TERMINLER = $bahadir->mssqlDb->Select("SELECT ISJOB FROM TERMIN WHERE SATIR_ID = $SIPARIS_SATIR_ID AND ISJOB = 0");
                $TERMIN_COUNT = $TERMIN_COUNT + count($TERMINLER);
            }
            return $TERMIN_COUNT;
        }
    }

    public function REFERANS_HAZIRLAMA_SURECI_TEKNIK_RESMI_ONAYLA_AFTER($PROCESS_UID, $APP_UID)
    {

        $bahadir = new Bahadir();

        $MSSQL_CASE_ID = $bahadir->mssqlDb->GET_MSSQL_CASE_ID($PROCESS_UID, $APP_UID, null, false);

        $REDDEDILEN_TEKNIK_RESIMLERIN_ATANACAGI_TEKNIK_RESSAM_IDLERI = [];

        $TERMINLER = $bahadir->mssqlDb->Select("SELECT *FROM TERMIN WHERE CASE_ID = $MSSQL_CASE_ID AND ISJOB = 1");

        foreach ($TERMINLER as $TERMIN)
        {
        	$SIPARIS_SATIR_ID = $TERMIN["SATIR_ID"];
            $ASSIGNED_PM_USER_UID = $TERMIN["ASSIGNED_PM_USER_UID"];
            $STOK_KODU = $TERMIN["ROW_INDEX"];

            $TALEP_SATIRLAR = $bahadir->mssqlDb->Select("SELECT *FROM TALEP_SATIR WHERE ID = $SIPARIS_SATIR_ID");

            foreach ($TALEP_SATIRLAR as $TALEP_SATIR)
            {
                $STOK_KART_ID = $TALEP_SATIR["STOK_KART_ID"];
            	$OZEL_URETIM = $TALEP_SATIR["OZEL_URETIM"];

                if($OZEL_URETIM == 1){
                    $TEKNIK_RESIMLER = $bahadir->mssqlDb->Select("SELECT ID, ISPROCESSING, CURRENT_SITUATION FROM TEKNIK_RESIM WHERE OZEL_URETIM_KODU = '$STOK_KODU'");
                } else {
                    $TEKNIK_RESIMLER = $bahadir->mssqlDb->Select("SELECT ID, ISPROCESSING, CURRENT_SITUATION FROM TEKNIK_RESIM WHERE STOK_KART_ID = $STOK_KART_ID");
                }

                foreach ($TEKNIK_RESIMLER as $TEKNIK_RESIM)
                {
                	if($TEKNIK_RESIM["CURRENT_SITUATION"] == SituationTypes::REDDEDILDI){

                        $varmi = false;
                        foreach ($REDDEDILEN_TEKNIK_RESIMLERIN_ATANACAGI_TEKNIK_RESSAM_IDLERI as $UID)
                        {
                            if($UID == $ASSIGNED_PM_USER_UID){
                                $varmi = true;
                            }
                        }

                        if($varmi == false){
                            array_push($REDDEDILEN_TEKNIK_RESIMLERIN_ATANACAGI_TEKNIK_RESSAM_IDLERI, $ASSIGNED_PM_USER_UID);
                        }

                    }
                }

            }
        }

        return $REDDEDILEN_TEKNIK_RESIMLERIN_ATANACAGI_TEKNIK_RESSAM_IDLERI;

    }

    public function REFERANS_HAZIRLAMA_SURECI_TALEP_OLUSTUR_AFTER($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID)
    {
        $bahadir = new Bahadir();
        $MSSQL_CASE_ID_AND_DOC_NUMBER = $bahadir->mssqlDb->GET_MSSQL_CASE_ID_AND_DOC_NUMBER($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, "REFERANS TALEP", false);
        $MSSQL_CASE_ID = $MSSQL_CASE_ID_AND_DOC_NUMBER["MSSQL_CASE_ID"];
        $DOC_NUMBER = $MSSQL_CASE_ID_AND_DOC_NUMBER["DOC_NUMBER"];
        return array('MSSQL_CASE_ID'=>$MSSQL_CASE_ID,'DOC_NUMBER'=>$DOC_NUMBER);
    }

    public function REFERANS_HAZIRLAMA_SURECI_RUTIN_REVIZE_ISLEMI_AFTER($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID){

        $ATANACAK_KULLANICI_IDLERI = [];
        $bahadir = new Bahadir();

        $REFERANS_TALEP_MSSQL_CASE_ID = $bahadir->mssqlDb->GET_MSSQL_CASE_ID($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, false);

        $TALEP_SATIRLARI = $bahadir->mssqlDb->Select("SELECT ID FROM TALEP_SATIR WHERE REFERANS_TALEP_CASE_ID = $REFERANS_TALEP_MSSQL_CASE_ID");
        foreach ($TALEP_SATIRLARI as $TALEP_SATIR)
        {
            $SIPARIS_SATIR_ID = $TALEP_SATIR["ID"];
            $TERMINLER = $bahadir->mssqlDb->Select("SELECT ASSIGNED_PM_USER_UID, ISJOB FROM TERMIN WHERE SATIR_ID = $SIPARIS_SATIR_ID AND ISJOB = 1");
            foreach ($TERMINLER as $TERMIN)
            {
                $USER_UID = $TERMIN["ASSIGNED_PM_USER_UID"];
                $varmi = false;
                foreach ($ATANACAK_KULLANICI_IDLERI as $UID)
                {
                    if($UID == $USER_UID){
                        $varmi = true;
                    }
                }

                if($varmi == false){
                    array_push($ATANACAK_KULLANICI_IDLERI, $USER_UID);
                }
            }
        }
        return $ATANACAK_KULLANICI_IDLERI;
    }

    public function REFERANS_HAZIRLAMA_SURECI_RUTIN_REVIZE_ISLEMI_AFTER2($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID){
        $bahadir = new Bahadir();
        $MSSQL_CASE_ID_AND_DOC_NUMBER = $bahadir->mssqlDb->GET_MSSQL_CASE_ID_AND_DOC_NUMBER($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, "REFERANS TALEP", false);
        $MSSQL_CASE_ID = $MSSQL_CASE_ID_AND_DOC_NUMBER["MSSQL_CASE_ID"];
        $DOC_NUMBER = $MSSQL_CASE_ID_AND_DOC_NUMBER["DOC_NUMBER"];
        return array('MSSQL_CASE_ID'=>$MSSQL_CASE_ID,'DOC_NUMBER'=>$DOC_NUMBER);
    }
}