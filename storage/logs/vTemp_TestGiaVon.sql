SELECT        DVBanLeVatTu.VatTuID, DVBanLeVatTu.GiaVon, DVBanLeVatTu.TienVon, DVBanLe.KhoHangXuatID, VatTu.MaVatTu, VatTu.TenVatTu_TiengViet
FROM            DVBanLe INNER JOIN
                         DVBanLeVatTu ON DVBanLe.ID = DVBanLeVatTu.DVBanLeID INNER JOIN
                         VatTu ON DVBanLeVatTu.VatTuID = VatTu.ID
WHERE        (DVBanLe.NgayCT >= CONVERT(DATETIME, '2019-12-19 00:00:00', 102))
GROUP BY DVBanLeVatTu.VatTuID, DVBanLeVatTu.GiaVon, DVBanLeVatTu.TienVon, DVBanLe.KhoHangXuatID, VatTu.MaVatTu, VatTu.TenVatTu_TiengViet
HAVING        (DVBanLeVatTu.TienVon < 0) AND (DVBanLe.KhoHangXuatID = 1)