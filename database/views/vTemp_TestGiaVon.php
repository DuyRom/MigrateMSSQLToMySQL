<?php

use App\Helpers\DataHelper;
use App\Helpers\ViewHelper;
use Illuminate\Support\Facades\DB;

class vTemp_TestGiaVon
{
    public static function create()
    {
        $viewName = 'vTemp_TestGiaVon';
        $viewDefinitionText = '';

        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $viewDefinitionText = "
                    SELECT DVBanLeVatTu.VatTuID, DVBanLeVatTu.GiaVon, DVBanLeVatTu.TienVon, DVBanLe.KhoHangXuatID, VatTu.MaVatTu, VatTu.TenVatTu_TiengViet
                    FROM DVBanLe
                    INNER JOIN DVBanLeVatTu ON DVBanLe.ID = DVBanLeVatTu.DVBanLeID
                    INNER JOIN VatTu ON DVBanLeVatTu.VatTuID = VatTu.ID
                    WHERE DVBanLe.NgayCT >= '2019-12-19 00:00:00'
                    GROUP BY DVBanLeVatTu.VatTuID, DVBanLeVatTu.GiaVon, DVBanLeVatTu.TienVon, DVBanLe.KhoHangXuatID, VatTu.MaVatTu, VatTu.TenVatTu_TiengViet
                    HAVING DVBanLeVatTu.TienVon < 0 AND DVBanLe.KhoHangXuatID = 1
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
