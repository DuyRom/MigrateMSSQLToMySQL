SELECT        GC.ID, GC.DVBanLeID, BL.SoCT, BL.NgayCT, GC.NgayGC, BL.KhoHangXuatID, GC.KhachHangID AS DoiTacGC, CASE WHEN GC.GiaCongBy IS NULL THEN bl.kythuatvien ELSE gc.giacongby END AS NhanVienGC, 
                         CASE WHEN S.XeID IS NULL THEN 3580 ELSE S.XeID END AS XeID, S.Model2, 'XuatBanCH' AS NghiepVu, 'GC' AS ChiTieu, GC.GiaCongID, GC.GiaBan AS DT, GC.ChiPhi AS GV
FROM            DVBanLe AS BL INNER JOIN
                         DVBanLeGiaCong AS GC ON BL.ID = GC.DVBanLeID INNER JOIN
                         XeSuaChua AS S ON BL.XeSuaChuaID = S.ID
WHERE        (BL.KyThuatVien IS NOT NULL)