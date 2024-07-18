<?php

use App\Helpers\DataHelper;
use App\Helpers\ViewHelper;
use Illuminate\Support\Facades\DB;

class V_ChiTienPowerApp
{
    public static function create()
    {
        $viewName = 'V_ChiTienPowerApp';
        $viewDefinitionText = '';

        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $viewDefinitionText = "
                    SELECT 
                        C.TenCuaHang, 
                        A.ID, 
                        A.KhoHangID, 
                        A.SoCT, 
                        A.NgayCT, 
                        A.KhachHangID, 
                        A.NguoiNhan, 
                        A.ChiTienLyDoID, 
                        A.TaiKhoanID, 
                        A.SoTien, 
                        A.GhiChu, 
                        A.ChuKy_NguoiNhan, 
                        A.Status1, 
                        A.Approval1By, 
                        A.Approval1At, 
                        A.Status2, 
                        A.Approval2By, 
                        A.Approval2At, 
                        A.CreatedBy, 
                        A.CreatedAt, 
                        A.UpdatedBy, 
                        A.UpdatedAt, 
                        A.DeletedBy, 
                        A.DeletedAt
                    FROM ChiTien AS A 
                    INNER JOIN KhoHang AS K ON A.KhoHangID = K.ID 
                    INNER JOIN CuaHang AS C ON K.CuaHangID = C.ID
                    WHERE A.NgayCT >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
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
