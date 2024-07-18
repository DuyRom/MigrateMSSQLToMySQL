SELECT         NV.ID, NV.HoTen, CV.TenChucVu, B_NhanVienKhoHang.KhoHangID, KhoHang.TenKhoHang, NV.CuaHangID, CH.TenCuaHang, NV.IDQT, NV.IsActive
FROM            KhoHang INNER JOIN
                         B_NhanVienKhoHang ON KhoHang.ID = B_NhanVienKhoHang.KhoHangID INNER JOIN
                         CuaHang AS CH ON KhoHang.CuaHangID = CH.ID INNER JOIN
                         NhanVien AS NV INNER JOIN
                         ChucVu AS CV ON NV.ChucVuID = CV.ID ON B_NhanVienKhoHang.NhanVienID = NV.ID