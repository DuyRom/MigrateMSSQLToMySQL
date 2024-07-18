SELECT   KM.DVBanLeID, BL.KhoHangXuatID, KM.NgayXuat, KM.SoLuong, KM.TienVon, CASE WHEN n.vattunhomid IN (1, 5) THEN 'PT' WHEN l.vattuloaiid IN (182, 188, 190, 194, 205, 206, 183) 
                         THEN 'Khac' WHEN l.vattuloaiid IN (184, 195, 196, 197, 198, 199, 200, 204) THEN 'Nhot' WHEN l.vattuloaiid IN (181, 187, 191) THEN 'PK' WHEN l.vattuloaiid IN (192, 193) 
                         THEN 'PTHN' WHEN l.vattuloaiid IN (201) THEN 'Nano' WHEN l.vattuloaiid IN (202) THEN 'KP' WHEN l.vattuloaiid IN (203) THEN 'BD' ELSE NULL END AS ChiTieu, KM.VatTuID
FROM         VatTuLoai AS N INNER JOIN
                         VatTu AS L ON N.ID = L.VatTuLoaiID INNER JOIN
                         DVBanLe AS BL INNER JOIN
                         DVBanLeKhuyenMai AS KM ON BL.ID = KM.DVBanLeID ON L.ID = KM.VatTuID