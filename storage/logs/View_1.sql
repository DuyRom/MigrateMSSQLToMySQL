SELECT        DVBanLeVatTu.VatTuID
FROM            DVBanLe INNER JOIN
                         DVBanLeVatTu ON DVBanLe.ID = DVBanLeVatTu.DVBanLeID
WHERE        (DVBanLe.NgayCT >= CONVERT(DATETIME, '2019-12-19 00:00:00', 102)) AND (DVBanLeVatTu.TienVon <= 0)
GROUP BY DVBanLeVatTu.VatTuID