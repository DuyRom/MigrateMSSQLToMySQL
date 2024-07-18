SELECT        B.ID, B.DVNoiBoID AS PhieuID, A.SoCT, A.NgayCT, A.KhoHangNhapID, B.VatTuID, 'NhapChuyen' AS NghiepVu, L.ChiTieu, '0' AS TenLyDo, B.SoLuong AS SL, B.ThanhTien AS GV
FROM            KhoHang AS K INNER JOIN
                         DVNoiBo AS A INNER JOIN
                         DVNoiBoVatTu AS B ON A.ID = B.DVNoiBoID ON K.ID = A.KhoHangXuatID INNER JOIN
                         VatTu AS V ON B.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID
WHERE        (A.IsXacNhan = 1) AND (K.KhoHangLoaiID = 2) AND a.ngayct >= '2019-12-19'
UNION ALL
SELECT        B.ID, B.DoiDonViID AS PhieuID, A.SoCT, A.NgayCT, A.KhoHangID AS KhoHangNhapID, B.VatTuID, 'NhapDoiDonVi' AS NghiepVu, L.ChiTieu, '0' AS TenLyDo, B.SoLuong AS SL, B.ThanhTien AS GV
FROM            DoiDonVi AS A INNER JOIN
                         DoiDonViChiTiet AS B ON A.ID = B.DoiDonViID INNER JOIN
                         VatTu AS V ON B.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID
WHERE        a.ngayct >= '2019-12-19'
UNION ALL
SELECT        B.ID, B.DVNhapKhacID AS PhieuID, A.SoCT, A.NgayCT, A.KhoHangNhapID, B.VatTuID, 'NhapKhac' AS NghiepVu, L.ChiTieu, LD.TenLyDo, B.SoLuong AS SL, B.ThanhTien AS GV
FROM            DVNhapKhac AS A INNER JOIN
                         DVNhapKhacVatTu AS B ON A.ID = B.DVNhapKhacID INNER JOIN
                         DVNhapKhacLyDo AS LD ON A.LyDoID = LD.ID INNER JOIN
                         VatTu AS V ON B.VatTuID = V.ID INNER JOIN
                         VatTuLoai AS L ON V.VatTuLoaiID = L.ID INNER JOIN
                         KhoHang AS K ON A.KhoHangNhapID = K.ID
WHERE        (A.NgayCT >= '2019-12-19') AND (K.KhoHangLoaiID = 2)