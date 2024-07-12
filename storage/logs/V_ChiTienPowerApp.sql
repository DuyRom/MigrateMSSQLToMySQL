SELECT        C.TenCuaHang, A.ID, A.KhoHangID, A.SoCT, A.NgayCT, A.KhachHangID, A.NguoiNhan, A.ChiTienLyDoID, A.TaiKhoanID, A.SoTien, A.GhiChu, A.ChuKy_NguoiNhan, A.Status1, A.Approval1By, A.Approval1At, A.Status2, 
                         A.Approval2By, A.Approval2At, A.CreatedBy, A.CreatedAt, A.UpdatedBy, A.UpdatedAt, A.DeletedBy, A.DeletedAt
FROM            ChiTien AS A INNER JOIN
                         KhoHang AS K ON A.KhoHangID = K.ID INNER JOIN
                         CuaHang AS C ON K.CuaHangID = C.ID
WHERE        (A.NgayCT >= CONVERT(date, DATEADD(day, - 30, NOW()), 105))