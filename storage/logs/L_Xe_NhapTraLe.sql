
SELECT   TL.ID, TL.SoCT, TL.NgayCT, TL.XeBanLeID, BL.NgayBan, BL.KhoHangXuatID, BL.KhachHangID, BL.XeNhapMuaID, M.SoKhung, M.SoMay, BL.TuVanID, BL.HinhThucBan, TL.ThanhTienXe, 
                         TL.PhiBaoBien AS ThanhTienBB, TL.GiaPhuKien AS ThanhTienPK, M.XeID, BL.GiaSonXe AS ThanhTienSon, M.DonGia_CoVAT AS TienVonXe, 
                         V_XeSonXeMax.GiaNhap AS TienVonSon, V_BaoBien_GV.SoTien AS TienVonBB, V_PK_ThanhTienVon_XeBanLe.TienVonPK
FROM         XeNhapTraLaiBanLe AS TL INNER JOIN
                         XeBanLe AS BL ON TL.XeBanLeID = BL.ID INNER JOIN
                         XeNhapMua AS M ON BL.XeNhapMuaID = M.ID LEFT OUTER JOIN
                         V_BaoBien_GV ON BL.ID = V_BaoBien_GV.XeBanLeID LEFT OUTER JOIN
                         V_PK_ThanhTienVon_XeBanLe ON BL.ID = V_PK_ThanhTienVon_XeBanLe.XeBanLeID LEFT OUTER JOIN
                         V_XeSonXeMax ON BL.XeNhapMuaID = V_XeSonXeMax.XeNhapMuaID