SELECT        B.ID, A.ID AS IDPhieu, A.SoCT, B.NgayXuat, L.VatTuLoaiID, B.ThanhTien, A.KhoHangXuatID, B.SoLuong, CASE WHEN S.XeID IS NULL THEN 3580 ELSE S.XeID END AS XeID, 
                         CASE WHEN b.ngayxuat < '2021-01-22' THEN a.nguoimoinano ELSE a.kiemtracuoi END AS KiemTraCuoi
FROM            DVBanLeVatTu AS B INNER JOIN
                         VatTu AS L ON B.VatTuID = L.ID INNER JOIN
                         DVBanLe AS A ON B.DVBanLeID = A.ID INNER JOIN
                         XeSuaChua AS S ON A.XeSuaChuaID = S.ID
WHERE        (L.VatTuLoaiID = 201) AND (A.NguoiMoiNano IS NOT NULL) AND (B.ThanhTien > 0) AND (B.NgayXuat < '2021-01-22') OR
                         (L.VatTuLoaiID = 201) AND (B.ThanhTien > 0) AND (A.KiemTraCuoi IS NOT NULL) AND (B.NgayXuat >= '2021-01-22')