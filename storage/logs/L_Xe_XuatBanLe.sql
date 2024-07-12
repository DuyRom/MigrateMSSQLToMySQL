SELECT        XeBanLe.ID, XeBanLe.SoCT, XeBanLe.NgayCT, XeBanLe.NgayBan, XeBanLe.XeID, A_BH_Xe_XuatBanCH.SoKhung, A_BH_Xe_XuatBanCH.SoMay
FROM            XeBanLe LEFT OUTER JOIN
                         A_BH_Xe_XuatBanCH ON XeBanLe.ID = A_BH_Xe_XuatBanCH.ID