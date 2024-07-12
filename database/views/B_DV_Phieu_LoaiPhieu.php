<?php
namespace App\databases\views;

use Illuminate\Support\Facades\DB;
use App\Helpers\ViewHelper;

class B_DV_Phieu_LoaiPhieu
{
    public static function create()
    {
        $viewName = 'B_DV_Phieu_LoaiPhieu';

        try {

            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $viewDefinitionText = "
                    SELECT NgayCT, KhoHangXuatID, 'kiemtradinhky' AS LoaiPhieu, kiemtradinhky AS SL
                    FROM B_DV_Phieu_XuatBanCH_All
                    WHERE kythuatvien IS NOT NULL AND kiemtracuoi IS NOT NULL AND kiemtradinhky <> 0
                    UNION ALL
                    SELECT NgayCT, KhoHangXuatID, 'IsNhot' AS LoaiPhieu, IsNhot AS SL
                    FROM B_DV_Phieu_XuatBanCH_All
                    WHERE kythuatvien IS NOT NULL AND kiemtracuoi IS NOT NULL AND IsNhot <> 0
                    UNION ALL
                    SELECT NgayCT, KhoHangXuatID, 'IsKP' AS LoaiPhieu, IsKP AS SL
                    FROM B_DV_Phieu_XuatBanCH_All
                    WHERE kythuatvien IS NOT NULL AND kiemtracuoi IS NOT NULL AND IsKP <> 0
                    UNION ALL
                    SELECT NgayCT, KhoHangXuatID, 'IsBD' AS LoaiPhieu, IsBD AS SL
                    FROM B_DV_Phieu_XuatBanCH_All
                    WHERE kythuatvien IS NOT NULL AND kiemtracuoi IS NOT NULL AND IsBD <> 0
                    UNION ALL
                    SELECT NgayCT, KhoHangXuatID, 'IsBHHVN' AS LoaiPhieu, IsBHHVN AS SL
                    FROM B_DV_Phieu_XuatBanCH_All
                    WHERE kythuatvien IS NOT NULL AND kiemtracuoi IS NOT NULL AND IsBHHVN <> 0
                    UNION ALL
                    SELECT NgayCT, KhoHangXuatID, 'IsBHTC' AS LoaiPhieu, IsBHTC AS SL
                    FROM B_DV_Phieu_XuatBanCH_All
                    WHERE kythuatvien IS NOT NULL AND kiemtracuoi IS NOT NULL AND IsBHTC <> 0
                    UNION ALL
                    SELECT NgayCT, KhoHangXuatID, 'IsSCLD' AS LoaiPhieu, IsSCLD AS SL
                    FROM B_DV_Phieu_XuatBanCH_All
                    WHERE kythuatvien IS NOT NULL AND kiemtracuoi IS NOT NULL AND IsSCLD <> 0
                ";

                $convertedQuery = ViewHelper::viewDefinitionTextHandle($viewDefinitionText);
                DB::connection('mysql')->statement("CREATE VIEW `$viewName` AS $convertedQuery");
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
