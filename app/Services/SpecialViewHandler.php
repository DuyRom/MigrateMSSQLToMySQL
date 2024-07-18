<?php

namespace App\Services;

use App\Helpers\ViewHelper;
use App\Helpers\DataHelper;
use Illuminate\Support\Facades\DB;

class SpecialViewHandler
{
    public static function handleSpecialView($viewName, $viewDefinition)
    {
        if (strpos($viewDefinition, 'FIRST_VALUE') !== false) {
            return "
                SELECT DISTINCT C.CuaHangID, B.XeID, X.MaXe, X.TenXe, 
                    (SELECT ApDungTu 
                    FROM (
                        SELECT C.CuaHangID, ApDungTu 
                        FROM XeBangGia AS A 
                        INNER JOIN XeBangGiaChiTiet AS B ON A.ID = B.XeBangGiaID 
                        INNER JOIN XeBangGiaCuaHang AS C ON A.ID = C.XeBangGiaID 
                        WHERE A.IsActive = 1 
                        ORDER BY ApDungTu DESC, C.ID DESC 
                        LIMIT 1
                    ) AS subquery 
                    WHERE subquery.CuaHangID = C.CuaHangID) AS ApDungTu, 
                    (SELECT GiaHaiQuan 
                    FROM (
                        SELECT C.CuaHangID, GiaHaiQuan 
                        FROM XeBangGia AS A 
                        INNER JOIN XeBangGiaChiTiet AS B ON A.ID = B.XeBangGiaID 
                        INNER JOIN XeBangGiaCuaHang AS C ON A.ID = C.XeBangGiaID 
                        WHERE A.IsActive = 1 
                        ORDER BY ApDungTu DESC, C.ID DESC 
                        LIMIT 1
                    ) AS subquery 
                    WHERE subquery.CuaHangID = C.CuaHangID) AS GiaHaiQuan 
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

    public static function create($viewName)
    {
        $specialViewHandlers = [
            'B_DV_Phieu_LoaiPhieu' => 'BDVPhieuLoaiPhieu',
            'B_BH_Xe_XuatBanCH' => 'BBHXeXuatBanCH',
            'A_BH_Xe_XuatBanCH' => 'ABHXeXuatBanCH',
            'B_BH_Xe_NhapTraCH' => 'BBHXeNhapTraCH',
            'A_BH_Xe_NhapTraCH' => 'ABHXeNhapTraCH',
            'vTemp_TestGiaVon' => 'vTempTestGiaVon',
            'V_ChiTienPowerApp' => 'VChiTienPowerApp',
            'View_1' => 'View1',
            'A_BH_Xe_BangGiaNhapMua' => 'ABHXeBangGiaNhapMua',
            'A_BH_Xe_BangGiaXuatBanCH' => 'ABHXeBangGiaXuatBanCH',
        ];

        if (array_key_exists($viewName, $specialViewHandlers)) {
            call_user_func([self::class, $specialViewHandlers[$viewName]]);
            return true;
        }

        return false;
    }

    public static function BDVPhieuLoaiPhieu()
    {
        $viewName = 'B_DV_Phieu_LoaiPhieu';
        $viewDefinitionText = '';

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
                DataHelper::migrateStatus($viewName, 0);
            } else {
                dump("View $viewName already exists in MySQL.");
            }
        } catch (\Exception $e) {
            dump("Error occurred while creating view $viewName.");
            DataHelper::migrateErrors(['viewName' => $viewName, 'viewDefinition' => $viewDefinitionText], $e);
        }
    }

    public static function ABHXeXuatBanCH()
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
    }

    public static function BBHXeXuatBanCH()
    {
        $viewName = 'B_BH_Xe_XuatBanCH';
        $viewDefinitionText = '';

        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $viewDefinitionText = "
                    SELECT L.ID, L.SoCT, L.NgayCT, L.NgayBan, L.KhoHangXuatID, L.KhachHangID, L.XeNhapMuaID, L.HinhThucBan, L.TuVanID, L.TuVanID AS NVTinhThuong, M.XeID, M.SoKhung, M.SoMay, 'XuatBanCH' AS NghiepVu, 
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
    }

    public static function BBHXeNhapTraCH()
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
    }

    public static function ABHXeNhapTraCH()
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
    }

    public static function vTempTestGiaVon()
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
    }

    public static function VChiTienPowerApp()
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
    }

    public static function View1()
    {
        $viewName = 'View_1';
        $viewDefinitionText = '';

        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $viewDefinitionText = "
                    SELECT 
                        DVBanLeVatTu.VatTuID
                    FROM 
                        DVBanLe 
                    INNER JOIN 
                        DVBanLeVatTu ON DVBanLe.ID = DVBanLeVatTu.DVBanLeID
                    WHERE 
                        DVBanLe.NgayCT >= '2019-12-19 00:00:00' 
                        AND DVBanLeVatTu.TienVon <= 0
                    GROUP BY 
                        DVBanLeVatTu.VatTuID
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
    }

    public static function ABHXeBangGiaXuatBanCH()
    {
        $viewName = 'A_BH_Xe_BangGiaXuatBanCH';
        $viewDefinitionText = "
            SELECT DISTINCT C.CuaHangID, B.XeID, X.MaXe, X.TenXe, 
                FIRST_VALUE(ApDungTu) OVER(PARTITION BY C.CuaHangID ORDER BY ApDungTu DESC, C.ID DESC) AS ApDungTu,
                FIRST_VALUE(GiaHaiQuan) OVER(PARTITION BY C.CuaHangID ORDER BY ApDungTu DESC, C.ID DESC) AS GiaHaiQuan
            FROM XeBangGia AS A 
            INNER JOIN XeBangGiaChiTiet AS B ON A.ID = B.XeBangGiaID 
            INNER JOIN XeBangGiaCuaHang AS C ON A.ID = C.XeBangGiaID 
            INNER JOIN Xe AS X ON B.XeID = X.ID 
            WHERE A.IsActive = 1
        ";

        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $convertedQuery = self::handleSpecialView($viewName, $viewDefinitionText);
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

    public static function ABHXeBangGiaNhapMua()
    {
        $viewName = 'A_BH_Xe_BangGiaNhapMua';
        $viewDefinitionText = "
            SELECT DISTINCT XeID, KhachHangID, X.MaXe, X.TenXe,
                FIRST_VALUE(ApDungTu) OVER(PARTITION BY KhachHangID ORDER BY ApDungTu DESC, M.ID DESC) AS ApDungTu,
                FIRST_VALUE(DonGia) OVER(PARTITION BY KhachHangID ORDER BY ApDungTu DESC, M.ID DESC) AS DonGia,
                FIRST_VALUE(M.ID) OVER(PARTITION BY KhachHangID ORDER BY ApDungTu DESC, M.ID DESC) AS ID
            FROM XeBangGiaNhapMua AS M 
            INNER JOIN Xe AS X ON M.XeID = X.ID;
        ";

        try {
            $exists = DB::connection('mysql')->select("SHOW FULL TABLES IN `" . env('DB_DATABASE') . "` WHERE TABLE_TYPE LIKE 'VIEW' AND Tables_in_" . env('DB_DATABASE') . " = ?", [$viewName]);

            if (empty($exists)) {
                $convertedQuery = self::handleSpecialView($viewName, $viewDefinitionText);
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
