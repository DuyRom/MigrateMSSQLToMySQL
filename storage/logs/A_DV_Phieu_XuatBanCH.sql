SELECT        ID, SoCT, NgayCT, CreatedAt, GioTaoPhieu, ThoiGianTraXeThucTe, KhoHangXuatID, KhachHangID, NghiepVu, SoKhung, SoMay, BienSo, NgayMua, TenModel, Km, KyThuatVien, KiemTraCuoi, KiemTraDinhKy, IsNhot, 
                         TongThanhTien, TongThanhTienTraLai, KhaoSatDiem
FROM            B_DV_Phieu_XuatBanCH_All
WHERE        (KyThuatVien IS NOT NULL) AND (KiemTraCuoi IS NOT NULL)