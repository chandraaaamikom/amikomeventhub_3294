<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Event;
use App\Services\TicketingService;
use Illuminate\Support\Facades\DB;

DB::beginTransaction();
try {
    $event = Event::create([
        'category_id' => Category::firstOrFail()->id, 'title' => 'Uji Harga Dinamis',
        'description' => 'Data uji rollback', 'date' => now()->addWeek(), 'location' => 'Ruang Uji',
        'price' => 100000, 'early_bird_price' => 80000, 'early_bird_ends_at' => now()->addDay(),
        'presale_price' => 90000, 'presale_ends_at' => now()->addDays(3), 'stock' => 10,
    ]);
    $coupon = Coupon::create(['code' => 'UJI10', 'type' => 'percent', 'value' => 10, 'minimum_purchase' => 0, 'is_active' => true]);
    $transaction = app(TicketingService::class)->reserve(
        [['event' => $event, 'quantity' => 2]],
        ['name' => 'Peserta Uji', 'email' => 'uji@example.test', 'phone' => '08123456789'], null, $coupon,
    );
    $item = $transaction->transactionItems()->first();
    if ($item->price !== 80000 || $transaction->discount_amount !== 16000 || $transaction->total_price !== 149000 || $coupon->fresh()->used_count !== 1) {
        throw new RuntimeException('Harga dinamis atau diskon kupon tidak sesuai.');
    }
    echo "PASS: Early Bird=80000, diskon=16000, total=149000, kupon-terpakai=1\n";
} finally {
    DB::rollBack();
}
