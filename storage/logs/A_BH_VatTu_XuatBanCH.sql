SELECT        BH.ID, BH.XeBanLeID, BL.SoCT, BL.NgayBan, BH.NgayXuat, BL.KhoHangXuatID, BL.KhachHangID, BH.VatTuID, 'XuatBanCH' AS NghiepVu, 'BHiem' AS ChiTieu, 1 AS SL, 0 AS TG, BH.GiaBanBH AS DT, BH.GiaVonBH AS GV, 
                         BL.XeID, BL.TuVanID, CASE WHEN BH.TuVanID IS NOT NULL THEN BH.TuVanID ELSE BL.TuVanID END AS NVTinhThuong, NULL AS KTV
FROM            XeBanLe AS BL INNER JOIN
                         XeBanLeBaoHiem AS BH ON BL.ID = BH.XeBanLeID LEFT OUTER JOIN
                         XeNhapTraLaiBanLe AS TL ON BL.ID = TL.XeBanLeID
WHERE        (TL.ID IS NULL) AND (BL.NgayBan IS NOT NULL)
UNION ALL
SELECT        PK.ID, PK.XeBanLeID, BL.SoCT, BL.NgayBan, PK.NgayXuat, BL.KhoHangXuatID, BL.KhachHangID, PK.VatTuID, 'XuatBanCH' AS NghiepVu, L.ChiTieu, PK.SoLuong AS SL, PK.DonGiaTG * PK.SoLuong AS TG, PK.ThanhTien AS DT, 
                         PK.TienVon AS GV, BL.XeID, isnull(PK.TuVanID, BL.TuVanID) AS TuVanID, isnull(PK.TuVanID, BL.TuVanID) AS NVTinhThuong, PK.NguoiThucHien AS KTV
FROM            XeBanLe AS BL INNER JOIN
                         XeBanLePhuKien AS PK ON BL.ID = PK.XeBanLeID INNER JOIN
                         VatTu AS V ON PK.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID
WHERE        (PK.SoLuong <> 0) AND (PK.NgayXuat IS NOT NULL)
UNION ALL
SELECT        KM.ID, KM.XeBanLeID, BL.SoCT, BL.NgayBan, KM.NgayXuat, BL.KhoHangXuatID, BL.KhachHangID, KM.VatTuID, CASE WHEN L.vattunhomid = 13 THEN 'XuatBanKMDT' ELSE 'XuatBanKMVT' END AS NghiepVu, L.ChiTieu, 
                         KM.SoLuong, 0 AS TG, CASE WHEN L.vattunhomid = 13 THEN - V.GiaXuat * Km.SoLuong ELSE 0 END AS DT, V.GiaNhap * Km.SoLuong AS GV, BL.XeID, BL.TuVanID, BL.TuVanID AS NVTinhThuong, NULL AS KTV
FROM            XeBanLe AS BL INNER JOIN
                         XeBanLeKhuyenMai AS KM ON BL.ID = KM.XeBanLeID INNER JOIN
                         VatTu AS V ON KM.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID
WHERE        (KM.SoLuong <> 0)
UNION ALL
SELECT        KM.ID, KM.XeBanSiID, BS.SoCT, BS.NgayCT, KM.NgayXuat, BS.KhoHangXuatID, BS.KhachHangID, KM.VatTuID, CASE WHEN L.vattunhomid = 13 THEN 'XuatBanKMDT' ELSE 'XuatBanKMVT' END AS NghiepVu, L.ChiTieu, 
                         KM.SoLuong, 0 AS TG, CASE WHEN L.vattunhomid = 13 THEN - V.GiaXuat * Km.SoLuong ELSE 0 END AS DT, V.GiaNhap * Km.SoLuong AS GV, NULL AS XeID, NULL AS TuVanID, NULL AS NVTinhThuong, NULL AS KTV
FROM            XeBanSi AS BS INNER JOIN
                         XeBanSiKhuyenMai AS KM ON BS.ID = KM.XeBanSiID INNER JOIN
                         VatTu AS V ON KM.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID
WHERE        (KM.SoLuong <> 0)
UNION ALL
SELECT        VC.ID, VC.XeBanLeID, BL.SoCT, BL.NgayBan, VC.NgayXuat, BL.KhoHangXuatID, BL.KhachHangID, VC.VatTuID, CASE WHEN L.vattunhomid = 13 THEN 'XuatBanKMDT' ELSE 'XuatBanKMVT' END AS NghiepVu, L.ChiTieu, 
                         VC.SoLuong, 0 AS TG, CASE WHEN L.vattunhomid = 13 THEN - V.GiaXuat * VC.SoLuong ELSE 0 END AS DT, V.GiaNhap * VC.SoLuong AS GV, BL.XeID, BL.TuVanID, BL.TuVanID AS NVTinhThuong, NULL AS KTV
FROM            XeBanLe AS BL INNER JOIN
                         XeBanLeVoucher AS VC ON BL.ID = VC.XeBanLeID INNER JOIN
                         VatTu AS V ON VC.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID
WHERE        (VC.SoLuong <> 0)