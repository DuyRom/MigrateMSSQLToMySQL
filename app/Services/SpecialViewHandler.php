<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SpecialViewHandler
{
    public static function handleSpecialView($viewName, $viewDefinition)
    {
        if (strpos($viewDefinition, 'FIRST_VALUE') !== false) {
            return "
                SELECT DISTINCT C.CuaHangID, B.XeID, X.MaXe, X.TenXe, 
                    (SELECT ApDungTu FROM 
                        (SELECT C.CuaHangID, ApDungTu FROM XeBangGia AS A 
                        INNER JOIN XeBangGiaChiTiet AS B ON A.ID = B.XeBangGiaID 
                        INNER JOIN XeBangGiaCuaHang AS C ON A.ID = C.XeBangGiaID 
                        WHERE A.IsActive = 1 
                        ORDER BY ApDungTu DESC, C.ID DESC LIMIT 1) as sub) as ApDungTu, 
                    (SELECT GiaHaiQuan FROM 
                        (SELECT C.CuaHangID, GiaHaiQuan FROM XeBangGia AS A 
                        INNER JOIN XeBangGiaChiTiet AS B ON A.ID = B.XeBangGiaID 
                        INNER JOIN XeBangGiaCuaHang AS C ON A.ID = C.XeBangGiaID 
                        WHERE A.IsActive = 1 
                        ORDER BY ApDungTu DESC, C.ID DESC LIMIT 1) as sub) as GiaHaiQuan 
                FROM XeBangGia AS A 
                INNER JOIN XeBangGiaChiTiet AS B ON A.ID = B.XeBangGiaID 
                INNER JOIN XeBangGiaCuaHang AS C ON A.ID = C.XeBangGiaID 
                INNER JOIN Xe AS X ON B.XeID = X.ID 
                WHERE A.IsActive = 1;
            ";
        }

        // Return the original view definition if no special handling is needed
        return $viewDefinition;
    }

    public static function createView($viewName, $viewDefinition)
    {
        try {
            DB::connection('mysql')->statement("CREATE VIEW `{$viewName}` AS {$viewDefinition}");
            return "View {$viewName} created successfully.";
        } catch (\Exception $e) {
            \Log::error("Error creating view {$viewName}: " . $e->getMessage());
            return "Error creating view {$viewName}: " . $e->getMessage();
        }
    }
}
