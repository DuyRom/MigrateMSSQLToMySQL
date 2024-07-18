<?php

use App\Helpers\DataHelper;
use App\Helpers\ViewHelper;
use Illuminate\Support\Facades\DB;

class A_BH_Xe_XuatBanCH
{
    public static function create()
    {
        $viewName = 'A_BH_Xe_XuatBanCH';
        $viewDefinitionText = '';

        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $viewDefinitionText = "
                    SELECT ID, SoCT, NgayCT, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, 
                           SUM(CASE WHEN A = 'TG' THEN ThanhTien ELSE 0 END) AS TG,
                           SUM(CASE WHEN A = 'DT' THEN ThanhTien ELSE 0 END) AS DT,
                           SUM(CASE WHEN A = 'GV' THEN ThanhTien ELSE 0 END) AS GV
                    FROM (
                        SELECT ID, SoCT, NgayCT, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, ThanhTien, 
                               SUBSTRING(ChiTieu, 1, 2) AS A, NghiepVu, SUBSTRING(ChiTieu, 3) AS ChiTieu
                        FROM (
                            SELECT ID, SoCT, NgayCT, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, DTXe AS ThanhTien, NghiepVu, 'DTXe' AS ChiTieu
                            FROM B_BH_Xe_XuatBanCH
                            WHERE DTXe <> 0
                            UNION ALL
                            SELECT ID, SoCT, NgayCT, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, DTBB AS ThanhTien, NghiepVu, 'DTBB' AS ChiTieu
                            FROM B_BH_Xe_XuatBanCH
                            WHERE DTBB <> 0
                            UNION ALL
                            SELECT ID, SoCT, NgayCT, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, GVXe AS ThanhTien, NghiepVu, 'GVXe' AS ChiTieu
                            FROM B_BH_Xe_XuatBanCH
                            WHERE GVXe <> 0
                            UNION ALL
                            SELECT ID, SoCT, NgayCT, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, GVBB AS ThanhTien, NghiepVu, 'GVBB' AS ChiTieu
                            FROM B_BH_Xe_XuatBanCH
                            WHERE GVBB <> 0
                            UNION ALL
                            SELECT ID, SoCT, NgayCT, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, TGXe AS ThanhTien, NghiepVu, 'TGXe' AS ChiTieu
                            FROM B_BH_Xe_XuatBanCH
                            WHERE TGXe <> 0
                            UNION ALL
                            SELECT ID, SoCT, NgayCT, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, TGBB AS ThanhTien, NghiepVu, 'TGBB' AS ChiTieu
                            FROM B_BH_Xe_XuatBanCH
                            WHERE TGBB <> 0
                        ) AS unpvt
                    ) AS X
                    GROUP BY ID, SoCT, NgayCT, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay
                    HAVING (SUM(CASE WHEN A = 'TG' THEN ThanhTien ELSE 0 END) <> 0) OR 
                           (SUM(CASE WHEN A = 'DT' THEN ThanhTien ELSE 0 END) <> 0) OR 
                           (SUM(CASE WHEN A = 'GV' THEN ThanhTien ELSE 0 END) <> 0)
                ";

                $convertedQuery = ViewHelper::viewDefinitionTextHandle($viewDefinitionText);
                DB::connection('mysql')->statement("CREATE VIEW `$viewName` AS $convertedQuery");
                dump("View $viewName created successfully in MySQL.");
                DataHelper::migrateStatus($viewName, 0);
            } else {
                dump("View $viewName already exists in MySQL.");
            }
        } catch (\Exception $e) {
            dump("Error occurred while creating view $viewName.");
            DataHelper::migrateErrors(['viewName' => $viewName, 'viewDefinition' => $viewDefinitionText], $e);
        }

        return [
            'viewName' => $viewName,
            'viewDefinitionText' => $viewDefinitionText
        ];
    }
}
