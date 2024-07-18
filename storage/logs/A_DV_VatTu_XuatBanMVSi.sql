SELECT        B.ID, B.DVBanSiID AS PhieuID, A.SoCT, A.NgayCT, A.KhoHangXuatID, A.KhachHangID, 3580 as XeID, B.VatTuID, L.ChiTieu, K.KyThuatVienID AS NVTinhThuong, 'XuatBanMV' AS NghiepVu, B.SoLuong AS SL, B.TyLeCK, B.ThanhTien AS DT, 
                         B.TienVon AS GV
FROM            DVBanSi AS A INNER JOIN
                         DVBanSiVatTu AS B ON A.ID = B.DVBanSiID INNER JOIN
                         VatTu AS V ON B.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID INNER JOIN
                         KhoHang AS K ON A.KhoHangXuatID = K.ID
WHERE        (A.NgayCT >= '2019-12-19') AND (K.KhoHangLoaiID = 2)
UNION ALL
SELECT        B.ID, B.DVBanBuonID AS PhieuID, A.SoCT, A.NgayCT, A.KhoHangXuatID, A.KhachHangID, 3580 as XeID, B.VatTuID, L.ChiTieu, K.KyThuatVienID AS NVTinhThuong, 'XuatBanSi' AS NghiepVu, B.SoLuong AS SL, IFNULL(B.HeSo, 0) AS TyLeCK, 
                         B.ThanhTien AS DT, B.TienVon AS GV
FROM            DVBanBuon AS A INNER JOIN
                         DVBanBuonVatTu AS B ON A.ID = B.DVBanBuonID INNER JOIN
                         VatTu AS V ON B.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID INNER JOIN
                         KhoHang AS K ON A.KhoHangXuatID = K.ID
WHERE        a.ngayct >= '2019-12-19'