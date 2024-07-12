SELECT        X.VatTuID, X.KhoHangXuatID, X.NAM, X.THANG, N.SL AS SLN, X.SL AS SLX, X.SL - IFNULL(N.SL, 0) AS SLTON
FROM            vTemp_PKBH AS X LEFT OUTER JOIN
                         vTemp_PKBH_Nhap AS N ON X.VatTuID = N.VatTuID AND X.NAM = N.NAM AND X.THANG = N.THANG AND X.KhoHangXuatID = N.KhoHangNhapID