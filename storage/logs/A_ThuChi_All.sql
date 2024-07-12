SELECT        T .ID, T .SoCT, T .NgayCT, T .ngayct AS NgayHachToan, T .TaiKhoanID, T .SoTien, A.KhoHangXuatID AS KhoHangID, A.khohangxuatid AS KhoHachToanID, A.KhachHangID, T .TacNghiep, T .LyDoID, NULL AS NguoiNopNhan, 
                         T .CreatedBy, NULL AS GhiChu
FROM            XeBanLe AS A INNER JOIN
                         XeBanLeThanhToan AS T ON A.ID = T .XeBanLeID
WHERE        T .SoTien > 0
UNION ALL
SELECT        T .ID, T .SoCT, T .NgayCT, T .ngayct AS NgayHachToan, T .TaiKhoanID, T .SoTien, A.KhoHangXuatID AS KhoHangID, A.khohangxuatid AS KhoHachToanID, A.KhachHangID, T .TacNghiep, T .LyDoID, NULL AS NguoiNopNhan, 
                         T .CreatedBy, NULL AS GhiChu
FROM            XeBanSi AS A INNER JOIN
                         XeBanSiThanhToan AS T ON A.ID = T .XeBanSiID
WHERE        T .SoTien > 0
UNION ALL
SELECT        ID, SoCT, NgayCT, NgayHachToan, TaiKhoanID, SoTien, KhoHangID, KhoHachToanID, KhachHangID, 0 AS TacNghiep, ThuTienLyDoID, NguoiNop AS NguoiNopNhan, T .CreatedBy, T .GhiChu
FROM            ThuTien AS T
WHERE        T .SoTien > 0
UNION ALL
SELECT        T .ID, T .SoCT, T .NgayCT, T .NgayHachToan, T .TaiKhoanID, T .SoTien, T .KhoHangID, T .KhoHachToanID, T .KhachHangID, 1 AS TacNghiep, T .ChiTienLyDoID AS LyDoID, NguoiNhan AS NguoiNopNhan, T .CreatedBy, 
                         T .GhiChu
FROM            ChiTien AS T
WHERE        T .SoTien > 0
UNION ALL
SELECT        S.ID, S.SoCT, S.NgayCT, S.ngayct AS NgayHachToan, S.TaiKhoanID, S.GiaNhap AS SoTien, S.KhoHangID, S.khohangid AS KhoHachToanID, NULL AS KhachHangID, 1 AS TacNghiep, 102 AS LyDoID, NULL AS NguoiNopNhan, 
                         S.CreatedBy, NULL AS GhiChu
FROM            XeSonXe AS S
WHERE        S.gianhap > 0 AND S.NgayCT IS NOT NULL
UNION ALL
SELECT        T .ID, T .SoCT, T .NgayCT, T .ngayct AS NgayHachToan, T .TaiKhoanID, T .SoTien, A.KhoHangXuatID AS KhoHangID, A.khohangxuatid AS KhoHachToanID, A.KhachHangID, T .TacNghiep, T .LyDoID, NULL AS NguoiNopNhan, 
                         T .CreatedBy, NULL AS GhiChu
FROM            DVBanLe AS A INNER JOIN
                         DVBanLeThanhToan AS T ON A.ID = T .DVBanLeID
WHERE        T .SoTien > 0
UNION ALL
SELECT        T .ID, T .SoCT, T .NgayCT, T .ngayct AS NgayHachToan, T .TaiKhoanID, T .SoTien, A.KhoHangXuatID AS KhoHangID, A.khohangxuatid AS KhoHachToanID, A.KhachHangID, T .TacNghiep, T .LyDoID, NULL AS NguoiNopNhan, 
                         T .CreatedBy, NULL AS GhiChu
FROM            DVBanSi AS A INNER JOIN
                         DVBanSiThanhToan AS T ON A.ID = T .DVBanSiID
WHERE        T .SoTien > 0
UNION ALL
SELECT        T .ID, T .SoCT, T .NgayCT, T .ngayct AS NgayHachToan, T .TaiKhoanID, T .SoTien, A.KhoHangXuatID AS KhoHangID, A.khohangxuatid AS KhoHachToanID, A.KhachHangID, T .TacNghiep, T .LyDoID, NULL AS NguoiNopNhan, 
                         T .CreatedBy, NULL AS GhiChu
FROM            DVBanBuon AS A INNER JOIN
                         DVBanBuonThanhToan AS T ON A.ID = T .DVBanBuonID
WHERE        T .SoTien > 0
UNION ALL
SELECT        G.ID, A.SoCT, G.NgayGC AS NgayCT, G.NgayGC AS NgayHachToan, T .TaiKhoanID, G.ChiPhi AS SoTien, A.KhoHangXuatID AS KhoHangID, A.khohangxuatid AS KhoHachToanID, NULL AS KhachHangID, 1 AS TacNghiep, 
                         103 AS LyDoID, NULL AS NguoiNopNhan, G.CreatedBy, NULL AS GhiChu
FROM            DVBanLe AS A INNER JOIN
                         V_TaiKhoanTienMat AS T ON A.KhoHangXuatID = T .KhoHangID INNER JOIN
                         DVBanLeGiaCong AS G ON A.ID = G.DVBanLeID
WHERE        G.ChiPhi > 0
UNION ALL
SELECT        ID, NULL AS SoCT, Thang AS NgayCT, Thang AS NgayHachToan, 20 AS TaiKhoanID, SoTien, KhoHangID, KhoHachToanID, NULL AS KhachHangID, 1 AS TacNghiep, CASE WHEN Loai IN ('Luong', 'DCTang', 'DCGiam', 'Phat', 
                         'Them') THEN 149 WHEN Loai IN ('Thuong') THEN 150 WHEN Loai IN ('BH-CN', 'BH-CT') THEN 152 ELSE NULL END AS LyDoID, NULL AS NguoiNopNhan, 1 AS CreatedBy, NULL AS GhiChu
FROM            BangLuong
WHERE        Loai <> 'CongNo'
UNION ALL
SELECT        A.ID, A.SoCT, B.NgayXuat AS NgayCT, B.NgayXuat AS NgayHachToan, 20 AS TaiKhoanID, B.TienVon AS SoTien, A.KhoHangXuatID AS KhoHangID, A.KhoHangXuatID AS KhoHachToanID, A.KhachHangID, 1 AS TacNghiep, 
                         151 AS LyDoID, NULL AS NguoiNopNhan, B.CreatedBy, B.GhiChu
FROM            XeBanLe AS A INNER JOIN
                         XeBanLeKhuyenMai AS B ON A.ID = B.XeBanLeID
UNION ALL
SELECT        ID, SoCT, NgayCT, NgayCT AS NgayHachToan, 20 AS TaiKhoanID, GV AS SoTien, KhoHangXuatID AS KhoHangID, KhoHangXuatID AS KhoHachToanID, KhachHangID, 1 AS TacNghiep, 
                         CASE WHEN lydo = 'Xuất khuyến mãi' THEN 104 WHEN lydo = 'Xuất tài sản' THEN 20 ELSE NULL END AS LyDoID, NULL AS NguoiNopNhan, CreatedBy, GhiChu
FROM            A_BH_Xe_XuatKhac
