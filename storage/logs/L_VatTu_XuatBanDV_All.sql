SELECT   B.ID, B.DVBanLeID, A.SoCT, A.NgayCT, L.VatTuNhomID, V.VatTuLoaiID, A.KhoHangXuatID, B.NgayXuat, B.VatTuID, B.SoLuong, B.DonGia, B.DonGiaTG, 
                         B.DonGia * B.SoLuong AS TienHang, IFNULL(B.DonGiaTG, 0) * B.SoLuong AS TienHangTG, B.ThanhTien AS ThanhTienVatTu, B.TienVon, B.IsMuaVe, B.BaoHanhID, B.MaBCKT, B.MaKNBH,
                          B.TinhTrangBaoHanh, B.NgayHVNThanhToan, B.TienHVNThanhToan, B.GhiChuBaoHanh, CASE WHEN V.vattuloaiid IN (181, 187, 191) OR
                         b.ismuave = 1 THEN k.kythuatvienid ELSE a.kythuatvien END AS KyThuatVien, L.ChiTieu
FROM         DVBanLe AS A INNER JOIN
                         DVBanLeVatTu AS B ON A.ID = B.DVBanLeID INNER JOIN
                         VatTu AS V ON B.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID INNER JOIN
                         KhoHang AS K ON A.KhoHangXuatID = K.ID
WHERE     (B.SoLuong <> 0)