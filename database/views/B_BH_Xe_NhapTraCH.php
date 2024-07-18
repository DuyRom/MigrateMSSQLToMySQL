<?php

use App\Helpers\DataHelper;
use App\Helpers\ViewHelper;
use Illuminate\Support\Facades\DB;

class B_BH_Xe_NhapTraCH
{
    public static function create()
    {
        $viewName = 'B_BH_Xe_NhapTraCH';
        $viewDefinitionText = '';

        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $viewDefinitionText = "
                    SELECT TL.ID, TL.SoCT, TL.NgayCT, TL.XeBanLeID, BL.NgayBan, BL.KhoHangXuatID, BL.KhachHangID, BL.XeNhapMuaID, BL.TuVanID, BL.TuVanID AS NVTinhThuong, M.XeID, BL.HinhThucBan, M.SoKhung, M.SoMay, 'NhapTraCH' AS NghiepVu, 
                    CAST(TL.ThanhTienXe - IFNULL(BL.TienVonSonXe, 0) AS SIGNED) AS DTXe, 
                    CAST(TL.PhiBaoBien - IFNULL(BL.BHXMThu, 0) AS SIGNED) AS DTBB, 
                    CAST(M.DonGia_CoVAT AS SIGNED) AS GVXe, 
                    CAST(GVBB.SoTien AS SIGNED) AS GVBB, 
                    CAST(TG.TienHangXeTG AS SIGNED) AS TGXe, 
                    CAST(TG.TienHangBaoBienTG AS SIGNED) AS TGBB
                    FROM XeNhapTraLaiBanLe AS TL 
                    INNER JOIN XeBanLe AS BL ON TL.XeBanLeID = BL.ID 
                    INNER JOIN XeNhapMua AS M ON BL.XeNhapMuaID = M.ID 
                    LEFT OUTER JOIN B_BaoBien_GV AS GVBB ON BL.ID = GVBB.XeBanLeID 
                    LEFT OUTER JOIN B_XeBanCHGiaTangGiam AS TG ON BL.ID = TG.XeBanLeID
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
