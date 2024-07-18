SELECT   ID AS NhanVienID, CASE WHEN nv.chucvuid IN (1, 2, 5) THEN 26 WHEN nv.chucvuid IN (9) THEN 36 WHEN nv.chucvuid IN (4, 7, 8, 24, 34, 37, 38, 39) THEN 28 WHEN nv.chucvuid IN (6, 10, 12, 13, 
                         16, 36, 43) AND nv.cuahangid = 1 THEN 2 WHEN nv.chucvuid IN (6, 10, 12, 13, 16, 36, 43) AND nv.cuahangid = 2 THEN 6 WHEN nv.chucvuid IN (6, 10, 12, 13, 16, 36, 43) AND 
                         nv.cuahangid = 3 THEN 10 WHEN nv.chucvuid IN (6, 10, 12, 13, 16, 36, 43) AND nv.cuahangid = 4 THEN 14 WHEN nv.chucvuid IN (6, 10, 12, 13, 16, 36, 43) AND 
                         nv.cuahangid = 5 THEN 18 WHEN nv.chucvuid IN (6, 10, 12, 13, 16, 36, 43) AND nv.cuahangid = 6 THEN 22 WHEN nv.chucvuid IN (17, 18, 19, 20, 22, 24, 25, 26, 35, 40, 41, 42) AND 
                         nv.cuahangid = 1 THEN 1 WHEN nv.chucvuid IN (17, 18, 19, 20, 22, 24, 25, 26, 35, 40, 41, 42) AND nv.cuahangid = 2 THEN 5 WHEN nv.chucvuid IN (17, 18, 19, 20, 22, 24, 25, 26, 35, 40, 41, 42) AND 
                         nv.cuahangid = 3 THEN 9 WHEN nv.chucvuid IN (17, 18, 19, 20, 22, 24, 25, 26, 35, 40, 41, 42) AND nv.cuahangid = 4 THEN 13 WHEN nv.chucvuid IN (17, 18, 19, 20, 22, 24, 25, 26, 35, 40, 41, 42) AND 
                         nv.cuahangid = 5 THEN 17 WHEN nv.chucvuid IN (17, 18, 19, 20, 22, 24, 25, 26, 35, 40, 41, 42) AND nv.cuahangid = 6 THEN 21 WHEN nv.chucvuid IN (17, 18, 19, 20, 22, 24, 25, 26, 35, 40, 41, 42) AND
                          nv.cuahangid = 12 THEN 37 ELSE NULL END AS KhoHangID
FROM         NhanVien AS NV