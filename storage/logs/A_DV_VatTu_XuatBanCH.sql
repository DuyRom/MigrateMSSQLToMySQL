SELECT        ID, DVBanLeID, SoCT, NgayCT, NgayXuat, KhoHangXuatID, VatTuID, KyThuatVien, NVTinhThuong, TuVanID, NghiepVu, ChiTieu, XeSuaChuaID, KhachHangID, XeID, SoKhung, SoMay, BienSo, SL, TG, DT, GV, IsMuaVe, 
                         BaoHanhID, MaBCKT, MaKNBH, TinhTrangBaoHanh, NgayHVNThanhToan, TienHVNThanhToan, GhiChuBaoHanh
FROM            B_DV_VatTu_XuatBanCH_All
WHERE        (NgayXuat >= '2019-12-19') AND (KyThuatVien IS NOT NULL) AND (TuVanID IS NOT NULL)