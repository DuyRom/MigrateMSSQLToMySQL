SELECT T.ID, T.DVNhapTraLaiBanSiID, A.SoCT, A.NgayCT, A.KhoHangNhapID, A.DVBanSiID, A.LyDo, T.VatTuID, T.SoLuong, T.ThanhTien AS TienHangVatTu, ROUND(A.TyLeCK * - (0.01 * T.ThanhTien), 0) AS TienHangVatTuTG, ROUND(T.ThanhTien - A.TyLeCK * 0.01 * T.ThanhTien, 0) AS ThanhTienVatTu, T.TienVon, CASE WHEN n.vattunhomid IN (1, 5) THEN 'PT' WHEN l.vattuloaiid IN (182, 188, 
         190, 194, 205, 206) THEN 'Khong' WHEN l.vattuloaiid IN (183) THEN 'Mu' WHEN l.vattuloaiid IN (184, 195, 196, 197, 198, 199, 200, 204) THEN 'Nhot' WHEN l.vattuloaiid IN (181, 187, 191) THEN 'PK' WHEN l.vattuloaiid IN (192, 193) THEN 'PTHN' WHEN l.vattuloaiid IN (201) THEN 'Nano' WHEN l.vattuloaiid IN (202) THEN 'KP' WHEN l.vattuloaiid IN (203) THEN 'BD' ELSE NULL END AS ChiTieu, 
         CASE WHEN khohangnhapid = 1 THEN 725 WHEN khohangnhapid = 5 THEN 726 WHEN khohangnhapid = 9 THEN 727 WHEN khohangnhapid = 13 THEN 728 WHEN khohangnhapid = 17 THEN 729 WHEN khohangnhapid = 21 THEN 730 ELSE NULL END AS KyThuatVien, KhachHangNhom.MaKhachHangNhom
FROM  KhachHangNhom INNER JOIN
         KhachHang ON KhachHangNhom.ID = KhachHang.KhachHangNhomID INNER JOIN
         DVBanSi ON KhachHang.ID = DVBanSi.KhachHangID INNER JOIN
         VatTuLoai AS N INNER JOIN
         VatTu AS L ON N.ID = L.VatTuLoaiID INNER JOIN
         DVNhapTraLaiBanSi AS A INNER JOIN
         DVNhapTraLaiBanSiChiTiet AS T ON A.ID = T.DVNhapTraLaiBanSiID ON L.ID = T.VatTuID ON DVBanSi.ID = A.DVBanSiID