<?php

use Illuminate\Support\Facades\DB;

class B_BH_Xe_XuatBanCH {
    public static function create() {
        $viewName = 'B_BH_Xe_XuatBanCH';
        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);
            
            if (empty($exists)) {
      
                $viewDefinitionText = "
                    CREATE VIEW `B_BH_Xe_XuatBanCH` AS
                    SELECT
                        L.ID, L.SoCT, L.NgayCT, L.NgayBan, L.KhoHangXuatID, L.KhachHangID, L.XeNhapMuaID, L.HinhThucBan, L.TuVanID, L.TuVanID AS NVTinhThuong, 
                        M.XeID, M.SoKhung, M.SoMay, 'XuatBanCH' AS NghiepVu,
                        CAST(L.DonGia + L.GiaThayVanh + IFNULL(TG.TienHangXeTG, 0) AS SIGNED) AS DTXe,
                        CAST(L.PhiBaoBien + IFNULL(TG.TienHangBaoBienTG, 0) - L.BHXMThu AS SIGNED) AS DTBB,
                        CAST(M.DonGia_CoVAT AS SIGNED) AS GVXe,
                        CAST(IFNULL(GVBB.SoTien, 0) AS SIGNED) AS GVBB,
                        CAST(TG.TienHangXeTG AS SIGNED) AS TGXe,
                        CAST(TG.TienHangBaoBienTG AS SIGNED) AS TGBB
                    FROM XeBanLe AS L
                    INNER JOIN XeNhapMua AS M ON L.XeNhapMuaID = M.ID
                    LEFT OUTER JOIN XeBanLePhuNano AS N ON L.ID = N.XeBanLeID
                    LEFT OUTER JOIN B_BaoBien_GV AS GVBB ON L.ID = GVBB.XeBanLeID
                    LEFT OUTER JOIN B_XeBanCHGiaTangGiam AS TG ON L.ID = TG.XeBanLeID
                    WHERE L.NgayBan IS NOT NULL;
                ";
                
                DB::connection('mysql')->statement($viewDefinitionText);
                dump("View $viewName created successfully in MySQL.");
            } else {
                dump("View $viewName already exists in MySQL.");
            }
        } catch (\Exception $e) {
            dump($e->getMessage());
            dump("Error occurred while creating view $viewName.");
        }
    }
}
