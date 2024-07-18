SELECT DISTINCT XeID, KhachHangID, X.MaXe, X.TenXe,
	                                    FIRST_VALUE(ApDungTu) OVER(PARTITION BY KhachHangID ORDER BY ApDungTu DESC, M.ID DESC) ApDungTu,
	                                    FIRST_VALUE(DonGia) OVER(PARTITION BY KhachHangID ORDER BY ApDungTu DESC, M.ID DESC) DonGia,
	                                    FIRST_VALUE(M.ID) OVER(PARTITION BY KhachHangID ORDER BY ApDungTu DESC, M.ID DESC) ID
                                    FROM XeBangGiaNhapMua AS M INNER JOIN
                                        Xe AS X ON M.XeID = X.ID;