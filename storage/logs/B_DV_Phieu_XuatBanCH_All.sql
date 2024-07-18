SELECT        BL.ID, BL.SoCT, BL.NgayCT, BL.CreatedAt, CAST(REPLACE(DATE_FORMAT(BL.CreatedAt, '%H:%i'), ':', '') AS SIGNED) AS GioTaoPhieu, BL.ThoiGianTraXeThucTe, BL.KhoHangXuatID, BL.KhachHangID, 'XuatBanCH' AS NghiepVu, 
                         S.SoKhung, S.SoMay, S.BienSo, BL.NgayMua, S.TenModel, BL.Km, BL.KyThuatVien, CASE WHEN bl.ngayct < '2021-01-22' AND bl.nguoimoinano IS NOT NULL THEN bl.nguoimoinano ELSE bl.kiemtracuoi END AS KiemTraCuoi, 
                         BL.KiemTraDinhKy, BL.IsBaoDuongToanBo, CAST(BL.IsThayNhot AS SIGNED) AS IsNhot, CAST(BL.IsVeSinhKimPhun AS SIGNED) AS IsKP, CAST(BL.IsVeSinhBuongDot AS SIGNED) AS IsBD, BL.IsLamNano, BL.IsLapPhuKien, 
                         BL.IsLapKhoaChongChom, BL.IsMuaVe, CAST(BL.IsBaoHanhHVN AS SIGNED) AS IsBHHVN, CAST(BL.IsBaoHanhThienChi AS SIGNED) AS IsBHTC, CAST(BL.IsBaoHanhLuuDong AS SIGNED) AS IsSCLD, BL.TongThanhTien, 
                         BL.TongThanhTienTraLai, BL.KhaoSatDiem
FROM            DVBanLe AS BL LEFT OUTER JOIN
                         XeSuaChua AS S ON BL.XeSuaChuaID = S.ID