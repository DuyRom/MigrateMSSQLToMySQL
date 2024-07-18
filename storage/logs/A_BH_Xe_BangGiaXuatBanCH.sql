SELECT DISTINCT C.CuaHangID, B.XeID, X.MaXe, X.TenXe,
                                        FIRST_VALUE(ApDungTu) OVER(PARTITION BY C.CuaHangID ORDER BY ApDungTu DESC, C.ID DESC) ApDungTu,
                                        FIRST_VALUE(GiaHaiQuan) OVER(PARTITION BY C.CuaHangID ORDER BY ApDungTu DESC, C.ID DESC) GiaHaiQuan
                                    FROM    XeBangGia AS A INNER JOIN
                                            XeBangGiaChiTiet AS B ON A.ID = B.XeBangGiaID INNER JOIN
                                            XeBangGiaCuaHang AS C ON A.ID = C.XeBangGiaID INNER JOIN
                                            Xe AS X ON B.XeID = X.ID
                                    WHERE (A.IsActive = 1);