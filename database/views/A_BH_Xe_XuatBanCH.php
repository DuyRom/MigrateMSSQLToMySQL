<?php

use Illuminate\Support\Facades\DB;

class A_BH_Xe_XuatBanCH
{
    public static function create()
    {
        $viewName = 'A_BH_Xe_XuatBanCH';

        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $viewDefinitionText = "
                    CREATE VIEW `A_BH_Xe_XuatBanCH` AS 
                    SELECT 
                        ID, 
                        SoCT, 
                        NgayCT, 
                        NgayBan, 
                        KhoHangXuatID, 
                        KhachHangID, 
                        XeNhapMuaID, 
                        TuVanID, 
                        NVTinhThuong, 
                        XeID, 
                        HinhThucBan, 
                        SoKhung, 
                        SoMay, 
                        NghiepVu,
                        SUM(CASE WHEN SUBSTRING(ChiTieu, 1, 2) = 'TG' THEN ThanhTien ELSE 0 END) AS TG,
                        SUM(CASE WHEN SUBSTRING(ChiTieu, 1, 2) = 'DT' THEN ThanhTien ELSE 0 END) AS DT,
                        SUM(CASE WHEN SUBSTRING(ChiTieu, 1, 2) = 'GV' THEN ThanhTien ELSE 0 END) AS GV
                    FROM (
                        SELECT 
                            ID, 
                            SoCT, 
                            NgayCT, 
                            NgayBan, 
                            KhoHangXuatID, 
                            KhachHangID, 
                            XeNhapMuaID, 
                            TuVanID, 
                            NVTinhThuong, 
                            XeID, 
                            HinhThucBan, 
                            SoKhung, 
                            SoMay, 
                            NghiepVu,
                            'DTXe' AS ChiTieu, DTXe AS ThanhTien
                        FROM B_BH_Xe_XuatBanCH
                        WHERE DTXe <> 0

                        UNION ALL

                        SELECT 
                            ID, 
                            SoCT, 
                            NgayCT, 
                            NgayBan, 
                            KhoHangXuatID, 
                            KhachHangID, 
                            XeNhapMuaID, 
                            TuVanID, 
                            NVTinhThuong, 
                            XeID, 
                            HinhThucBan, 
                            SoKhung, 
                            SoMay, 
                            NghiepVu,
                            'DTBB' AS ChiTieu, DTBB AS ThanhTien
                        FROM B_BH_Xe_XuatBanCH
                        WHERE DTBB <> 0

                        UNION ALL

                        SELECT 
                            ID, 
                            SoCT, 
                            NgayCT, 
                            NgayBan, 
                            KhoHangXuatID, 
                            KhachHangID, 
                            XeNhapMuaID, 
                            TuVanID, 
                            NVTinhThuong, 
                            XeID, 
                            HinhThucBan, 
                            SoKhung, 
                            SoMay, 
                            NghiepVu,
                            'GVXe' AS ChiTieu, GVXe AS ThanhTien
                        FROM B_BH_Xe_XuatBanCH
                        WHERE GVXe <> 0

                        UNION ALL

                        SELECT 
                            ID, 
                            SoCT, 
                            NgayCT, 
                            NgayBan, 
                            KhoHangXuatID, 
                            KhachHangID, 
                            XeNhapMuaID, 
                            TuVanID, 
                            NVTinhThuong, 
                            XeID, 
                            HinhThucBan, 
                            SoKhung, 
                            SoMay, 
                            NghiepVu,
                            'GVBB' AS ChiTieu, GVBB AS ThanhTien
                        FROM B_BH_Xe_XuatBanCH
                        WHERE GVBB <> 0

                        UNION ALL

                        SELECT 
                            ID, 
                            SoCT, 
                            NgayCT, 
                            NgayBan, 
                            KhoHangXuatID, 
                            KhachHangID, 
                            XeNhapMuaID, 
                            TuVanID, 
                            NVTinhThuong, 
                            XeID, 
                            HinhThucBan, 
                            SoKhung, 
                            SoMay, 
                            NghiepVu,
                            'TGXe' AS ChiTieu, TGXe AS ThanhTien
                        FROM B_BH_Xe_XuatBanCH
                        WHERE TGXe <> 0

                        UNION ALL

                        SELECT 
                            ID, 
                            SoCT, 
                            NgayCT, 
                            NgayBan, 
                            KhoHangXuatID, 
                            KhachHangID, 
                            XeNhapMuaID, 
                            TuVanID, 
                            NVTinhThuong, 
                            XeID, 
                            HinhThucBan, 
                            SoKhung, 
                            SoMay, 
                            NghiepVu,
                            'TGBB' AS ChiTieu, TGBB AS ThanhTien
                        FROM B_BH_Xe_XuatBanCH
                        WHERE TGBB <> 0
                    ) AS unpvt
                    GROUP BY 
                        ID, 
                        SoCT, 
                        NgayCT, 
                        NgayBan, 
                        KhoHangXuatID, 
                        KhachHangID, 
                        XeNhapMuaID, 
                        TuVanID, 
                        NVTinhThuong, 
                        XeID, 
                        HinhThucBan, 
                        SoKhung, 
                        SoMay, 
                        NghiepVu
                    HAVING 
                        TG <> 0 OR 
                        DT <> 0 OR 
                        GV <> 0
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
