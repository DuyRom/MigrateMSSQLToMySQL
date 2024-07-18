SELECT    NV.ID, NV.HoTen, CV.TenChucVu, L_NhanVienKhoHang.KhoHangID, KhoHang.TenKhoHang, NV.CuaHangID, CH.TenCuaHang, NV.IDQT, NV.IsActive
FROM         KhoHang INNER JOIN
                         L_NhanVienKhoHang ON KhoHang.ID = L_NhanVienKhoHang.KhoHangID INNER JOIN
                         CuaHang AS CH ON KhoHang.CuaHangID = CH.ID INNER JOIN
                         NhanVien AS NV INNER JOIN
                         ChucVu AS CV ON NV.ChucVuID = CV.ID ON L_NhanVienKhoHang.NhanVienID = NV.ID