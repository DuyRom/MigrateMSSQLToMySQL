SELECT    NgayCT, KhoHangXuatID, REPLACE(LoaiPhieu, 'KiemTraDinhKy', 'IsKTDK') AS LoaiPhieu, COUNT(SL) AS SL
FROM         B_DV_Phieu_LoaiPhieu
GROUP BY NgayCT, KhoHangXuatID, LoaiPhieu
ORDER BY NgayCT