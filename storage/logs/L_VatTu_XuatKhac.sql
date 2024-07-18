SELECT   B.ID, B.DVXuatKhacID, A.SoCT, A.NgayCT, A.KhoHangXuatID, A.LyDoID, LD.TenLyDo, A.GhiChu, B.VatTuID, B.SoLuong, B.TienVon, CASE WHEN n.vattunhomid IN (1, 5) 
                         THEN 'PT' WHEN l.vattuloaiid IN (182, 188, 190, 194, 205, 206, 183) THEN 'Khac' WHEN l.vattuloaiid IN (184, 195, 196, 197, 198, 199, 200, 204) THEN 'Nhot' WHEN l.vattuloaiid IN (181, 187, 191) 
                         THEN 'PK' WHEN l.vattuloaiid IN (192, 193) THEN 'PTHN' WHEN l.vattuloaiid IN (201) THEN 'Nano' WHEN l.vattuloaiid IN (202) THEN 'KP' WHEN l.vattuloaiid IN (203) THEN 'BD' ELSE NULL 
                         END AS ChiTieu
FROM         DVXuatKhac AS A INNER JOIN
                         DVXuatKhacVatTu AS B ON A.ID = B.DVXuatKhacID INNER JOIN
                         DVXuatKhacLyDo AS LD ON A.LyDoID = LD.ID INNER JOIN
                         VatTu AS L ON B.VatTuID = L.ID INNER JOIN
                         VatTuLoai AS N ON L.VatTuLoaiID = N.ID