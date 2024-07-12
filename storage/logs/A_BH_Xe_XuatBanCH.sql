SELECT   *
FROM         (SELECT   ID, SoCT, NgayCT, NgayBan, KhoHangXuatID, KhachHangID, XeNhapMuaID, TuVanID, NVTinhThuong, XeID, HinhThucBan, SoKhung, SoMay, ThanhTien, SUBSTRING(ChiTieu, 1, 
                                                    2) AS A, NghiepVu, SUBSTRING(ChiTieu, 3, LEN(ChiTieu)) AS ChiTieu
                           FROM         (SELECT *, 'DTXe' AS `ChiTieu`, `DTXe` AS `ThanhTien` FROM `B_BH_Xe_XuatBanCH` WHERE `DTXe` <> 0 UNION ALL SELECT *, 'DTBB' AS `ChiTieu`, `DTBB` AS `ThanhTien` FROM `B_BH_Xe_XuatBanCH` WHERE `DTBB` <> 0 UNION ALL SELECT *, 'GVXe' AS `ChiTieu`, `GVXe` AS `ThanhTien` FROM `B_BH_Xe_XuatBanCH` WHERE `GVXe` <> 0 UNION ALL SELECT *, 'GVBB' AS `ChiTieu`, `GVBB` AS `ThanhTien` FROM `B_BH_Xe_XuatBanCH` WHERE `GVBB` <> 0 UNION ALL SELECT *, 'TGXe' AS `ChiTieu`, `TGXe` AS `ThanhTien` FROM `B_BH_Xe_XuatBanCH` WHERE `TGXe` <> 0 UNION ALL SELECT *, 'TGBB' AS `ChiTieu`, `TGBB` AS `ThanhTien` FROM `B_BH_Xe_XuatBanCH` WHERE `TGBB` <> 0)  AS unpvt) AS X PIVOT (Sum(ThanhTien) FOR 
                         A IN (`$1`, `$1`, `$1`)) AS pvt
WHERE     (TG <> 0) OR
                         (DT <> 0) OR
                         (GV <> 0)