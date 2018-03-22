<?php
class BTriggers
{
    public function PROFORMA_OLUSTURMA_SURECI_URETIM_RECETESI_TANIMLA_AFTER_TRIGGER($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, $CREATE_NEW_CASE_MODE, $PARENT_MSSQL_CASE_ID){

        $bahadir = new Bahadir();
        $SIPARISLER = $bahadir->mssqlDb->Select("SELECT ID,TALEP_TIPI FROM SIPARIS WHERE CASE_ID = $PARENT_MSSQL_CASE_ID");

        foreach ($SIPARISLER as $SIPARIS)
        {
            $SIPARIS_ID = $SIPARIS["ID"];
            $TALEP_TIPI = $SIPARIS["TALEP_TIPI"];

        	$TALEP_SATIRLAR = $bahadir->mssqlDb->Select("SELECT *FROM TALEP_SATIR WHERE SIPARIS_ID = $SIPARIS_ID AND OZEL_URETIM = 1");
            return array('OZEL_URETIM_SAYISI'=>count($TALEP_SATIRLAR), 'TALEP_TIPI'=>$TALEP_TIPI);
        }
        
    }

    public function PROFORMA_OLUSTURMA_SURECI_TALEP_DEGERLENDIR_AFTER_TRIGGER($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, $PARENT_MSSQL_CASE_ID){

        $bahadir = new Bahadir();
        $MSSQL_CASE_ID = $bahadir->mssqlDb->GET_MSSQL_CASE_ID($PROCESS_UID, $PM_CASE_ID, $PM_USER_UID, false, $PARENT_MSSQL_CASE_ID);
        $SONUC = $bahadir->mssqlDb->Select("SELECT ID,DEGERLENDIRME_SONUC FROM SIPARIS WHERE DEGERLENDIRME_CASE_ID = $MSSQL_CASE_ID");
        $SIPARIS_ID = $SONUC[0]["ID"];
        $DEGERLENDIRME_SONUC = $SONUC[0]["DEGERLENDIRME_SONUC"];
        $DEGERLENDIRME_ACIKLAMA = $SONUC[0]["DEGERLENDIRME_ACIKLAMA"];
        //$OZEL_URETIM_COUNT = $bahadir->mssqlDb->Select("SELECT COUNT(*) AS OZEL_URETIM_URUN_SAYISI FROM TALEP_SATIR WHERE OZEL_URETIM = 1 AND SIPARIS_ID = $SIPARIS_ID");
        //$STANDART_URUN_COUNT = $bahadir->mssqlDb->Select("SELECT COUNT(*) AS STANDART_URUN_SAYISI FROM TALEP_SATIR WHERE OZEL_URETIM = 0 AND SIPARIS_ID = $SIPARIS_ID");

        $OZEL_URETIM_COUNT = 0;
        $STANDART_URUN_COUNT = 0;
        $TEKNIK_RESIM_OLANLAR = 0;
        $TEKNIK_RESIM_OLMAYANLAR = 0;
        $TEKNIK_RESIM_GUNCEL_OLANLAR = 0;
        $TEKNIK_RESIM_GUNCEL_OLMAYANLAR = 0;
        $RECETESI_OLMAYAN_STOK_KART_IDLER = [];

        $RECETESI_VE_TEKNIK_RESIM_KONTROL = [];

        $CURRENT_SITUATION = 'REFERANS_TALEBI_DEGERLENDIRILIYOR';

        foreach ($bahadir->mssqlDb->Select("SELECT *FROM TALEP_SATIR WHERE SIPARIS_ID = $SIPARIS_ID") as $TALEP_SATIR)
        {
            $TALEP_SATIR_ID = $TALEP_SATIR["ID"];
            $OZEL_URETIM = $TALEP_SATIR["OZEL_URETIM"];
            $TEKNIK_RESIM_OZEL_URETIM_KODU_FIELD = "";

            $TEKNIK_RESMI_VARMI = false;
            $RECETESI_VARMI = false;            

            if($OZEL_URETIM == 1){

                $OZEL_URETIM_COUNT = $OZEL_URETIM_COUNT + 1;
                //$OZEL_URETIM_KODU = $TALEP_SATIR["OZEL_URETIM_KODU"];

                //$STOK_KART_VARMI = $bahadir->mssqlDb->Select("SELECT ID FROM STOK_KART WHERE STOK_KODU = '$OZEL_URETIM_KODU'");

                //$INSERT_OZEL_URETIM = $bahadir->mssqlDb->ExecQuery("INSERT INTO STOK_KART(CASE_ID, STOK_KODU, STATU_ID, STOK_KATEGORI_ID, KART_ACILIS_TARIHI, ACIKLAMA_TR, OLCU_BIRIM_ID, OZEL_URETIM_KARTI, TEMP, SILINDI, ISPROCESSING) VALUES($MSSQL_CASE_ID, '$OZEL_URETIM_KODU', 3, 1, GETDATE(), '', 2, 1, 1, 0, 1)");
                //$STOK_KART_ID = $bahadir->mssqlDb->IDENT_CURRENT("STOK_KART");

                //$UPDATE_TALEP_SATIR = $INSERT_OZEL_URETIM = $bahadir->mssqlDb->ExecQuery("UPDATE TALEP_SATIR SET STOK_KART_ID = $STOK_KART_ID WHERE ID = $TALEP_SATIR_ID");

            }else{
                $STANDART_URUN_COUNT = $STANDART_URUN_COUNT + 1;
                $STOK_KART_ID = $TALEP_SATIR["STOK_KART_ID"];
            }

            $TEKNIK_RESIMLER = $bahadir->mssqlDb->Select("SELECT ID FROM TEKNIK_RESIM WHERE STOK_KART_ID = $STOK_KART_ID");

            if(count($TEKNIK_RESIMLER) == 0){
                $TEKNIK_RESIM_OLMAYANLAR = $TEKNIK_RESIM_OLMAYANLAR + 1;   
                if($OZEL_URETIM == 1){              
                    $INSERT = $bahadir->mssqlDb->ExecQuery("INSERT INTO TEKNIK_RESIM (CASE_ID, STOK_KART_ID, OZEL_URETIM_KODU, CURRENT_SITUATION, KAYIT_TARIHI, SILINDI, GUNCEL, ISPROCESSING) VALUES($MSSQL_CASE_ID, $STOK_KART_ID, '$OZEL_URETIM_KODU', '$CURRENT_SITUATION', GETDATE(), 0, 0, 1)");       
                }else{
                    $INSERT = $bahadir->mssqlDb->ExecQuery("INSERT INTO TEKNIK_RESIM (CASE_ID, STOK_KART_ID, CURRENT_SITUATION, KAYIT_TARIHI, SILINDI, GUNCEL, ISPROCESSING) VALUES($MSSQL_CASE_ID, $STOK_KART_ID, '$CURRENT_SITUATION', GETDATE(), 0, 0, 1)");       
                }
                $TEKNIK_RESMI_VARMI = false;
            }else{

                $TEKNIK_RESIMLER = $bahadir->mssqlDb->Select("SELECT ID FROM TEKNIK_RESIM WHERE DATALENGTH(PDF) > 0 AND STOK_KART_ID = $STOK_KART_ID");           
                foreach ($TEKNIK_RESIMLER as $TEKNIK_RESIM)
                {
                    $TEKNIK_RESIM_OLANLAR = $TEKNIK_RESIM_OLANLAR + 1;
                    if($TEKNIK_RESIM["GUNCEL"] == 1){
                        $TEKNIK_RESIM_GUNCEL_OLANLAR = $TEKNIK_RESIM_GUNCEL_OLANLAR + 1;
                    } else {
                        $TEKNIK_RESIM_GUNCEL_OLMAYANLAR = $TEKNIK_RESIM_GUNCEL_OLMAYANLAR + 1;
                    }
                    $UPDATE = $bahadir->mssqlDb->ExecQuery("UPDATE TEKNIK_RESIM SET ISPROCESSING = 1, CURRENT_SITUATION = '$CURRENT_SITUATION' WHERE STOK_KART_ID = $STOK_KART_ID");
                }
                $TEKNIK_RESMI_VARMI = true;
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

            if(count($RECETESI_OLMAYAN_STOK_KART_IDLER) > 0){
                $RECETESI_VARMI = true;
            }else{    
                $RECETESI_VARMI = false;
            }
            array_push($RECETESI_VE_TEKNIK_RESIM_KONTROL, array('TEKNIK_RESMI_VARMI'=>$TEKNIK_RESMI_VARMI,'RECETESI_VARMI'=>$RECETESI_VARMI));
        }

        $SADECE_RECETESI_EKSIK_OLANLAR = 0;
        $SADECE_TEKNIK_RESMI_EKSIK_OLANLAR = 0;
        $RECETESI_VE_TEKNIK_RESMI_EKSIK_OLANLAR = 0;

        for ($i = 0; $i < count($RECETESI_VE_TEKNIK_RESIM_KONTROL); $i++)
        {
        	if($RECETESI_VE_TEKNIK_RESIM_KONTROL[i]["TEKNIK_RESMI_VARMI"] == 0 && $RECETESI_VE_TEKNIK_RESIM_KONTROL[i]["RECETESI_VARMI"] == 0)
            {
                $RECETESI_VE_TEKNIK_RESMI_EKSIK_OLANLAR = $RECETESI_VE_TEKNIK_RESMI_EKSIK_OLANLAR + 1;
            }

            if($RECETESI_VE_TEKNIK_RESIM_KONTROL[i]["TEKNIK_RESMI_VARMI"] == 0 && $RECETESI_VE_TEKNIK_RESIM_KONTROL[i]["RECETESI_VARMI"] == 1)
            {
                $SADECE_TEKNIK_RESMI_EKSIK_OLANLAR = $SADECE_TEKNIK_RESMI_EKSIK_OLANLAR + 1;
            }

            if($RECETESI_VE_TEKNIK_RESIM_KONTROL[i]["TEKNIK_RESMI_VARMI"] == 1 && $RECETESI_VE_TEKNIK_RESIM_KONTROL[i]["RECETESI_VARMI"] == 0)
            {
                $SADECE_RECETESI_EKSIK_OLANLAR = $SADECE_RECETESI_EKSIK_OLANLAR + 1;
            }
        }
        
        return array(
                'SADECE_RECETESI_EKSIK_OLANLAR'=>$SADECE_RECETESI_EKSIK_OLANLAR,
                'SADECE_TEKNIK_RESMI_EKSIK_OLANLAR'=>$SADECE_TEKNIK_RESMI_EKSIK_OLANLAR,
                'RECETESI_VE_TEKNIK_RESMI_EKSIK_OLANLAR'=>$RECETESI_VE_TEKNIK_RESMI_EKSIK_OLANLAR,
                'RECETESI_VE_TEKNIK_RESIM_KONTROL'=>$RECETESI_VE_TEKNIK_RESIM_KONTROL,
                'DEGERLENDIRME_SONUC'=>$DEGERLENDIRME_SONUC,
                'DEGERLENDIRME_ACIKLAMA'=>$DEGERLENDIRME_ACIKLAMA,
                'TEKNIK_RESMI_OLANLAR'=>$TEKNIK_RESIM_OLANLAR,
                'TEKNIK_RESMI_OLMAYANLAR'=>$TEKNIK_RESIM_OLMAYANLAR,
                'TEKNIK_RESIM_GUNCEL_OLANLAR'=>$TEKNIK_RESIM_GUNCEL_OLANLAR,
                'TEKNIK_RESIM_GUNCEL_OLMAYANLAR'=>$TEKNIK_RESIM_GUNCEL_OLMAYANLAR,
                'RECETESI_OLMAYAN_URUN_SAYISI'=>count($RECETESI_OLMAYAN_STOK_KART_IDLER),
                'OZEL_URETIM_URUN_SAYISI'=>$OZEL_URETIM_COUNT,
                'STANDART_URUN_SAYISI'=>$STANDART_URUN_COUNT);
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

    public function REFERANS_HAZIRLAMA_SURECI_TEKNIK_RESMI_ONAYLA_AFTER($PROCESS_UID, $APP_UID, $MSSQL_CASE_ID)
    {

        $bahadir = new Bahadir();

        $CASE_ID = $MSSQL_CASE_ID > 0 ? $MSSQL_CASE_ID : $bahadir->mssqlDb->GET_MSSQL_CASE_ID($PROCESS_UID, $APP_UID, null, false);

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
                    $STOK_KODU = $TALEP_SATIR["OZEL_URETIM_KODU"];
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