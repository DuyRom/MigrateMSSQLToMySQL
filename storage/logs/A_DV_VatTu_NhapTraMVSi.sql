SELECT        T .ID, A.DVBanSiID AS PhieuBanID, T .DVNhapTraLaiBanSiID AS PhieuTraLaiID, A.SoCT, A.NgayCT, A.KhoHangNhapID, S.KhachHangID,3580 as XeID, T .VatTuID, L.ChiTieu, K.KyThuatVienID AS NVTinhThuong, 'NhapTraMV' AS NghiepVu, 
                         A.LyDo, T .SoLuong AS SL, T .ThanhTien AS DT, SVT.TienVon AS GV
FROM            VatTuLoai AS L INNER JOIN
                         VatTu AS V ON L.ID = V.VatTuLoaiID INNER JOIN
                         DVNhapTraLaiBanSi AS A INNER JOIN
                         DVNhapTraLaiBanSiChiTiet AS T ON A.ID = T .DVNhapTraLaiBanSiID ON V.ID = T .VatTuID INNER JOIN
                         DVBanSi AS S ON A.DVBanSiID = S.ID INNER JOIN
                         KhoHang AS K ON A.KhoHangNhapID = K.ID INNER JOIN
                         DVBanSiVatTu AS SVT ON T .VatTuID = SVT.VatTuID AND T .DVBanSiID = SVT.DVBanSiID
WHERE        (A.NgayCT >= '2019-12-19') AND (K.KhoHangLoaiID = 2)
UNION ALL
SELECT        TL.ID, A.DVBanBuonID AS PhieuBanID, TL.DVNhapTraLaiBanBuonID AS PhieuTraLaiID, A.SoCT, A.NgayCT, A.KhoHangNhapID, BS.KhachHangID,3580 as XeID, TL.VatTuID, L.ChiTieu, K.KyThuatVienID AS NVTinhThuong, 
                         'NhapTraSi' AS NghiepVu, A.LyDo, TL.SoLuong AS SL, TL.ThanhTien AS DT, BSVT.TienVon AS GV
FROM            DVNhapTraLaiBanBuon AS A INNER JOIN
                         DVBanBuon AS BS ON A.DVBanBuonID = BS.ID INNER JOIN
                         DVNhapTraLaiBanBuonChiTiet AS TL ON A.ID = TL.DVNhapTraLaiBanBuonID INNER JOIN
                         VatTuLoai AS L INNER JOIN
                         VatTu AS V ON L.ID = V.VatTuLoaiID ON TL.VatTuID = V.ID INNER JOIN
                         DVBanBuonVatTu AS BSVT ON TL.DVBanBuonID = BSVT.DVBanBuonID AND TL.VatTuID = BSVT.VatTuID INNER JOIN
                         KhoHang AS K ON A.KhoHangNhapID = K.ID
WHERE        a.ngayct >= '2019-12-19'