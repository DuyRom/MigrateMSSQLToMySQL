SELECT   NgayCT, KhoHangXuatID, LoaiPhieu, SL
FROM         (SELECT   *
                           FROM         B_DV_Phieu_XuatBanCH_All
                           WHERE     kythuatvien IS NOT NULL AND kiemtracuoi IS NOT NULL) p  AS unpvt
WHERE     sl <> 0