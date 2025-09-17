
<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->string('coupon_code', 50);
            $table->string('user_id');
            $table->string('ride_id')->nullable();
            $table->string('order_id')->nullable();
            $table->decimal('discount_amount', 8, 2);
            $table->decimal('original_amount', 8, 2);
            $table->decimal('final_amount', 8, 2);
            $table->timestamp('used_at');
            $table->boolean('firebase_synced')->default(false);
            $table->timestamp('firebase_synced_at')->nullable();
            $table->timestamps();

            $table->foreign('coupon_code')->references('code')->on('coupons')->onDelete('cascade');
            $table->index('coupon_code');
            $table->index('user_id');
            $table->index('used_at');
            $table->index('firebase_synced');
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupon_usages');
    }
};