SELECT        B.ID, B.DVBanLeID, A.SoCT, A.NgayCT, B.NgayXuat, A.KhoHangXuatID, B.VatTuID, A.KyThuatVien, CASE WHEN V.vattuloaiid IN (181, 187, 191) OR
                         B.Ismuave = 1 THEN k.kythuatvienid ELSE a.kythuatvien END AS NVTinhThuong, CASE WHEN B.ngayxuat >= '2021-01-22' THEN A.kiemtracuoi WHEN (B.ngayxuat < '2021-01-22' AND A.nguoimoinano IS NULL) 
                         THEN A.kiemtracuoi ELSE A.nguoimoinano END AS KiemTraCuoi, L.ChiTieu, A.XeSuaChuaID, A.KhachHangID, CASE WHEN S.XeID IS NULL THEN 3580 ELSE S.XeID END AS XeID, S.SoKhung, S.SoMay, S.BienSo, 
                         'XuatBanCH' AS NghiepVu, B.SoLuong AS SL, IFNULL(B.DonGiaTG, 0) * B.SoLuong AS TG, B.ThanhTien AS DT, B.TienVon AS GV, B.IsMuaVe, B.BaoHanhID, B.MaBCKT, B.MaKNBH, B.TinhTrangBaoHanh, B.NgayHVNThanhToan, 
                         B.TienHVNThanhToan, B.GhiChuBaoHanh, CASE WHEN B.TuVanID IS NOT NULL THEN B.TuVanID ELSE CASE WHEN V.IsTinhThuong = 0 THEN A.KiemTraCuoi ELSE NULL END END AS TuVanID
FROM            DVBanLe AS A INNER JOIN
                         DVBanLeVatTu AS B ON A.ID = B.DVBanLeID INNER JOIN
                         VatTu AS V ON B.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID INNER JOIN
                         KhoHang AS K ON A.KhoHangXuatID = K.ID INNER JOIN
                         XeSuaChua AS S ON A.XeSuaChuaID = S.ID
WHERE        (B.SoLuong <> 0)