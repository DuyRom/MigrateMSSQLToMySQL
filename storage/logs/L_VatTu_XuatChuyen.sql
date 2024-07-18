SELECT B.ID, B.XeNoiBoID AS NoiBoID, A.SoCT, A.NgayCT, A.KhoHangXuatID, A.KhoHangNhapID, PK.VatTuID, PK.SoLuong, PK.TienVon AS ThanhTienVonVatTu, 'PK' AS ChiTieu
FROM   XeNoiBoPhuKien AS PK INNER JOIN
             XeNoiBoChiTiet AS B ON PK.XeNoiBoChiTietID = B.ID INNER JOIN
             XeNoiBo AS A ON B.XeNoiBoID = A.ID
WHERE (A.IsXacNhan = 1)
UNION ALL
SELECT B.ID, B.DVNoiBoID AS NoiBoID, A.SoCT, A.NgayCT, A.KhoHangXuatID, A.KhoHangNhapID, B.VatTuID, B.SoLuong, B.ThanhTien AS ThanhTienVonVatTu, CASE WHEN n.vattunhomid IN (1, 5) THEN 'PT' WHEN l.vattuloaiid IN (182, 188, 190, 194, 205, 206, 183) 
             THEN 'Khac' WHEN l.vattuloaiid IN (184, 195, 196, 197, 198, 199, 200, 204) THEN 'Nhot' WHEN l.vattuloaiid IN (181, 187, 191) THEN 'PK' WHEN l.vattuloaiid IN (192, 193) THEN 'PTHN' WHEN l.vattuloaiid IN (201) THEN 'Nano' WHEN l.vattuloaiid IN (202) THEN 'KP' WHEN l.vattuloaiid IN (203) 
             THEN 'BD' ELSE NULL END AS ChiTieu
FROM   VatTuLoai AS N INNER JOIN
             VatTu AS L ON N.ID = L.VatTuLoaiID INNER JOIN
             DVNoiBo AS A INNER JOIN
             DVNoiBoVatTu AS B ON A.ID = B.DVNoiBoID ON L.ID = B.VatTuID
WHERE (A.IsXacNhan = 1)