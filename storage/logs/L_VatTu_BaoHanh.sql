SELECT        ID, DVBanLeID, SoCT, NgayCT, VatTuNhomID, VatTuLoaiID, ChiTieu, KhoHangXuatID, NgayXuat, VatTuID, SoLuong, DonGia, DonGiaTG, TienHang, TienHangTG, ThanhTienVatTu, TienVon, IsMuaVe, BaoHanhID, MaBCKT, 
                         MaKNBH, TinhTrangBaoHanh, NgayHVNThanhToan, TienHVNThanhToan, GhiChuBaoHanh, KyThuatVien
FROM            L_VatTu_XuatBanDV_All
WHERE        (BaoHanhID <> 0)