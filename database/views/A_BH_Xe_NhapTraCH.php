<?php

use App\Helpers\DataHelper;
use App\Helpers\ViewHelper;
use Illuminate\Support\Facades\DB;

class A_BH_Xe_NhapTraCH
{
    public static function create()
    {
        $viewName = 'A_BH_Xe_NhapTraCH';
        $viewDefinitionText = '';

        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $viewDefinitionText = "
                    SELECT *
                    FROM (
                        SELECT ID, SoCT, NgayCT, XeBanLeID, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, ThanhTien, SUBSTRING(ChiTieu, 1, 2) AS A, NghiepVu, SUBSTRING(ChiTieu, 3, CHAR_LENGTH(ChiTieu)) AS ChiTieu
                        FROM (
                            SELECT *, 'DTXe' AS ChiTieu, DTXe AS ThanhTien FROM B_BH_Xe_NhapTraCH WHERE DTXe <> 0
                            UNION ALL
                            SELECT *, 'DTBB' AS ChiTieu, DTBB AS ThanhTien FROM B_BH_Xe_NhapTraCH WHERE DTBB <> 0
                            UNION ALL
                            SELECT *, 'GVXe' AS ChiTieu, GVXe AS ThanhTien FROM B_BH_Xe_NhapTraCH WHERE GVXe <> 0
                            UNION ALL
                            SELECT *, 'GVBB' AS ChiTieu, GVBB AS ThanhTien FROM B_BH_Xe_NhapTraCH WHERE GVBB <> 0
                            UNION ALL
                            SELECT *, 'TGXe' AS ChiTieu, TGXe AS ThanhTien FROM B_BH_Xe_NhapTraCH WHERE TGXe <> 0
                            UNION ALL
                            SELECT *, 'TGBB' AS ChiTieu, TGBB AS ThanhTien FROM B_BH_Xe_NhapTraCH WHERE TGBB <> 0
                        ) AS unpvt
                    ) AS pvt
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
