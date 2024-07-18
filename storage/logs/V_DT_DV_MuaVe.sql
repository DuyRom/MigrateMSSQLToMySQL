SELECT B.ID, B.DVBanSiID, A.SoCT, A.NgayCT, A.SoHD, A.NgayHD, A.KhoHangXuatID, A.KhachHangID, B.VatTuID, B.SoLuong, N.VatTuNhomID, L.VatTuLoaiID, CASE WHEN n.vattunhomid IN (1, 5) THEN 'PT' WHEN l.vattuloaiid IN (182, 188, 190, 194, 205, 206) THEN 'Khong' WHEN l.vattuloaiid IN (183) THEN 'Mu' WHEN l.vattuloaiid IN (184, 195, 196, 197, 198, 199, 200, 204) 
         THEN 'Nhot' WHEN l.vattuloaiid IN (181, 187, 191) THEN 'PK' WHEN l.vattuloaiid IN (203, 202, 201, 192, 193) THEN 'PTHN' WHEN l.vattuloaiid = 207 THEN 'Khoa' ELSE NULL END AS ChiTieu, 
         (CASE WHEN A.KhoHangXuatID = 1 THEN 725 WHEN A.KhoHangXuatID = 5 THEN 726 WHEN A.KhoHangXuatID = 9 THEN 727 WHEN A.KhoHangXuatID = 13 THEN 728 WHEN A.KhoHangXuatID = 17 THEN 729 WHEN A.KhoHangXuatID = 21 THEN 730 ELSE NULL END) AS KyThuatVien, B.ThanhTien AS TienHang, ROUND(A.TyLeCK * - (0.01 * B.ThanhTien * 0.001), 0) * 1000 AS TienHangTG, 
         ROUND((B.ThanhTien - A.TyLeCK * 0.01 * B.ThanhTien) * 0.001, 0) * 1000 AS ThanhTienVatTu, B.TienVon, KhachHangNhom.MaKhachHangNhom
FROM  DVBanSi AS A INNER JOIN
         DVBanSiVatTu AS B ON A.ID = B.DVBanSiID INNER JOIN
         VatTu AS L ON B.VatTuID = L.ID INNER JOIN
         VatTuLoai AS N ON L.VatTuLoaiID = N.ID INNER JOIN
         KhachHang ON A.KhachHangID = KhachHang.ID INNER JOIN
         KhachHangNhom ON KhachHang.KhachHangNhomID = KhachHangNhom.ID