SELECT   L.ID, L.SoCT, L.NgayCT, L.NgayBan, L.KhoHangXuatID, L.KhachHangID, L.XeNhapMuaID, L.HinhThucBan, L.TuVanID, L.TuVanID AS NVTinhThuong, M.XeID, M.SoKhung, M.SoMay, 
                         'XuatBanCH' AS NghiepVu, CAST(L.DonGia + L.GiaThayVanh + IFNULL(TG.TienHangXeTG, 0 AS SIGNED)) AS DTXe, CAST(L.PhiBaoBien + IFNULL(TG.TienHangBaoBienTG, 0 AS SIGNED) 
                         - L.BHXMThu) AS DTBB, CAST(M.DonGia_CoVAT AS SIGNED) AS GVXe, CAST(IFNULL(GVBB.SoTien, 0 AS SIGNED)) AS GVBB, CAST(TG.TienHangXeTG AS SIGNED) AS TGXe, CAST(TG.TienHangBaoBienTG AS SIGNED) AS TGBB
FROM         XeBanLe AS L INNER JOIN
                         XeNhapMua AS M ON L.XeNhapMuaID = M.ID LEFT OUTER JOIN
                         XeBanLePhuNano AS N ON L.ID = N.XeBanLeID LEFT OUTER JOIN
                         B_BaoBien_GV AS GVBB ON L.ID = GVBB.XeBanLeID LEFT OUTER JOIN
                         B_XeBanCHGiaTangGiam AS TG ON L.ID = TG.XeBanLeID
WHERE     (L.NgayBan IS NOT NULL)