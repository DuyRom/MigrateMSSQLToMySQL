SELECT        B.ID, B.DVNoiBoID AS PhieuID, A.SoCT, A.NgayCT, A.KhoHangXuatID, B.VatTuID, 'XuatChuyen' AS NghiepVu, L.ChiTieu, '0' AS TenLyDo, B.SoLuong AS SL, B.ThanhTien AS GV
FROM            KhoHang AS K INNER JOIN
                         DVNoiBo AS A INNER JOIN
                         DVNoiBoVatTu AS B ON A.ID = B.DVNoiBoID ON K.ID = A.KhoHangXuatID INNER JOIN
                         VatTu AS V ON B.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID
WHERE        (A.IsXacNhan = 1) AND (K.KhoHangLoaiID = 2) AND (B.SoLuong <> 0) AND a.ngayct >= '2019-12-19'
UNION ALL
SELECT        A.ID, A.ID AS PhieuID, A.SoCT, A.NgayCT, A.KhoHangID, A.VatTuID, 'XuatDoiDonVi' AS NghiepVu, L.ChiTieu, '0' AS TenLyDo, A.SoLuong AS SL, A.ThanhTien AS GV
FROM            DoiDonVi AS A INNER JOIN
                         VatTu AS V ON A.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID
WHERE        (A.NgayCT >= '2019-12-19')
UNION ALL
SELECT        B.ID, B.DVXuatKhacID AS PhieuID, A.SoCT, A.NgayCT, A.KhoHangXuatID, B.VatTuID, 'XuatKhac' AS NghiepVu, L.ChiTieu, LD.TenLyDo, B.SoLuong AS SL, B.TienVon AS GV
FROM            DVXuatKhac AS A INNER JOIN
                         DVXuatKhacVatTu AS B ON A.ID = B.DVXuatKhacID INNER JOIN
                         DVXuatKhacLyDo AS LD ON A.LyDoID = LD.ID INNER JOIN
                         VatTu AS V ON B.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID INNER JOIN
                         KhoHang AS K ON A.KhoHangXuatID = K.ID
WHERE        (B.SoLuong <> 0) AND (A.NgayCT >= '2019-12-19') AND (K.KhoHangLoaiID = 2)