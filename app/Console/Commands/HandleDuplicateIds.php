<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HandleDuplicateIds extends Command
{
    protected $signature = 'fix:duplicate-IDs';
    protected $description = 'Handle duplicate IDs in the database';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        // Tắt kiểm tra khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Xử lý ID trùng lặp
        $this->removeDuplicateIDs();

        // Bật lại kiểm tra khóa ngoại
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Cập nhật giá trị tự động tăng cho ID
        $this->updateAutoIncrement();

        $this->info('Duplicate IDs handled successfully.');
    }

    protected function removeDuplicateIDs()
    {
        $duplicates = DB::table('DVBanLeKiemTra')
            ->select('ID')
            ->groupBy('ID')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('ID');

        foreach ($duplicates as $ID) {
            DB::table('DVBanLeKiemTra')
                ->where('ID', $ID)
                ->orderBy('ID') // Thêm mệnh đề orderBy
                ->chunk(100, function ($records) {
                    $firstRecord = $records->shift(); // Giữ lại bản ghi đầu tiên
                    foreach ($records as $record) {
                        DB::table('DVBanLeKiemTra')->where('ID', $record->ID)->delete();
                    }
                });

            $this->info("Duplicate ID {$ID} removed.");
        }
    }

    protected function updateAutoIncrement()
    {
        // Lấy giá trị ID lớn nhất
        $maxID = DB::table('DVBanLeKiemTra')->max('ID');

        // Cập nhật giá trị tự động tăng cho ID
        DB::statement("ALTER TABLE DVBanLeKiemTra AUTO_INCREMENT = " . ($maxID + 1) . ";");
    }
}
