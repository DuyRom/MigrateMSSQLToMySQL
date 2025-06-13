<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Mail\MigrationCompleted;
use Illuminate\Support\Facades\Mail;
use App\Notifications\MigrationErrorNotification;
use Illuminate\Support\Facades\Notification;

class AutoIncrementPk
{
    public static function migrateTables($toLower = false)
    {
        // Lấy danh sách bảng từ SQL Server
        $tables = DB::connection('sqlsrv')->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");

        $successfulMigrations = [];
        $failedMigrations = [];

        // Tắt kiểm tra khóa ngoại trong MySQL
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($tables as $table) {
                $tableName = $toLower ? strtolower($table->TABLE_NAME) : $table->TABLE_NAME;

                try {
                    // Kiểm tra xem bảng có tồn tại trong MySQL không
                    if (!Schema::connection('mysql')->hasTable($tableName)) {
                        throw new \Exception("Table {$tableName} does not exist in MySQL database.");
                    }

                    // Kiểm tra xem cột id có tồn tại trong MySQL không
                    if (!Schema::connection('mysql')->hasColumn($tableName, 'id')) {
                        throw new \Exception("Table {$tableName} does not have an 'id' column in MySQL.");
                    }

                    // Kiểm tra xem cột id trong SQL Server có phải là identity không
                    $isAutoIncrement = DB::connection('sqlsrv')->selectOne(
                        "SELECT COLUMNPROPERTY(object_id(TABLE_NAME), COLUMN_NAME, 'IsIdentity') as IsIdentity
                         FROM INFORMATION_SCHEMA.COLUMNS
                         WHERE TABLE_NAME = ? AND COLUMN_NAME = 'id'",
                        [$table->TABLE_NAME]
                    );

                    if ($isAutoIncrement && $isAutoIncrement->IsIdentity) {
                        // Lấy giá trị id lớn nhất trong bảng MySQL
                        $maxId = DB::connection('mysql')->table($tableName)->max('id') ?? 0;
                        $maxId = (int) $maxId;

                        // Thiết lập cột id thành AUTO_INCREMENT và set giá trị tiếp theo
                        DB::connection('mysql')->statement("ALTER TABLE " . DB::connection('mysql')->getPdo()->quote($tableName) . " MODIFY COLUMN id BIGINT AUTO_INCREMENT");
                        DB::connection('mysql')->statement("ALTER TABLE " . DB::connection('mysql')->getPdo()->quote($tableName) . " AUTO_INCREMENT = " . ($maxId + 1));

                        dump("Table {$tableName}: Set id column to AUTO_INCREMENT with next value " . ($maxId + 1));

                        // Cập nhật trạng thái migration
                        DB::connection('mysql')->table('migration_status')->updateOrInsert(
                            ['table_name' => $tableName],
                            [
                                'records_migrated' => 1, // Chỉ đếm số bảng được xử lý
                                'migration_success' => true,
                                'updated_at' => now(),
                            ]
                        );

                        $successfulMigrations[] = $tableName;
                    } else {
                        throw new \Exception("Table {$tableName} does not have an auto-incrementing 'id' column in SQL Server.");
                    }
                } catch (\Exception $e) {
                    dump("Error migrating table {$tableName}: {$e->getMessage()}");

                    // Lưu lỗi vào bảng migration_errors
                    DB::connection('mysql')->table('migration_errors')->insert([
                        'table_name' => $tableName,
                        'error_message' => $e->getMessage(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Cập nhật trạng thái migration
                    DB::connection('mysql')->table('migration_status')->updateOrInsert(
                        ['table_name' => $tableName],
                        [
                            'records_migrated' => 0,
                            'migration_success' => false,
                            'updated_at' => now(),
                        ]
                    );

                    $failedMigrations[] = $tableName;

                    // Gửi thông báo lỗi
                    Notification::route('mail', config('mail.to.address'))
                        ->notify(new MigrationErrorNotification($tableName, $e->getMessage()));

                    \Log::error("Error migrating table {$tableName}: {$e->getMessage()}");
                }
            }
        } finally {
            // Đảm bảo bật lại kiểm tra khóa ngoại
            DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // Gửi email thông báo hoàn tất
        Mail::to(config('mail.to.address'))->send(new MigrationCompleted($successfulMigrations, $failedMigrations));
    }
}