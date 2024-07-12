SELECT *
FROM   (SELECT ID, SoCT, NgayCT, XeBanLeID, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, ThanhTien, SUBSTRING(ChiTieu, 1, 2) AS A, NghiepVu, SUBSTRING(ChiTieu, 3, LEN(ChiTieu)) 
                           AS ChiTieu
             FROM    (SELECT *, 'DTXe' AS `ChiTieu`, `DTXe` AS `ThanhTien` FROM `B_BH_Xe_NhapTraCH` WHERE `DTXe` <> 0 UNION ALL SELECT *, 'DTBB' AS `ChiTieu`, `DTBB` AS `ThanhTien` FROM `B_BH_Xe_NhapTraCH` WHERE `DTBB` <> 0 UNION ALL SELECT *, 'GVXe' AS `ChiTieu`, `GVXe` AS `ThanhTien` FROM `B_BH_Xe_NhapTraCH` WHERE `GVXe` <> 0 UNION ALL SELECT *, 'GVBB' AS `ChiTieu`, `GVBB` AS `ThanhTien` FROM `B_BH_Xe_NhapTraCH` WHERE `GVBB` <> 0 UNION ALL SELECT *, 'TGXe' AS `ChiTieu`, `TGXe` AS `ThanhTien` FROM `B_BH_Xe_NhapTraCH` WHERE `TGXe` <> 0 UNION ALL SELECT *, 'TGBB' AS `ChiTieu`, `TGBB` AS `ThanhTien` FROM `B_BH_Xe_NhapTraCH` WHERE `TGBB` <> 0)  AS unpvt) AS X  AS pvt