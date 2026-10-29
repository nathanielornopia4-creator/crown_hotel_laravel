<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('users', function(Blueprint $t) {
   $t->increments('user_id'); $t->string('full_name',150); $t->string('phone',30); $t->string('email',150);
   $t->string('username',80)->unique(); $t->string('password_hash'); $t->enum('role',['customer','Hotel Administrator/Manager','Front Desk Staff','Hotel Staff'])->default('customer');
   $t->timestamp('created_at')->useCurrent();
  });
  Schema::create('rooms', function(Blueprint $t) {
   $t->increments('room_id'); $t->string('room_number',30)->unique(); $t->enum('room_type',['Standard Room','Deluxe Room','Family Size','Suite']);
   $t->string('image',255)->default(''); $t->integer('capacity')->default(2); $t->decimal('rate',12,2);
   $t->enum('status',['Available','Occupied','Reserved','Maintenance','Cleaning','Inactive'])->default('Available'); $t->timestamp('created_at')->useCurrent();
  });
  Schema::create('reservations', function(Blueprint $t) {
   $t->increments('reservation_id'); $t->string('reference_no',30)->unique(); $t->unsignedInteger('user_id'); $t->unsignedInteger('room_id');
   $t->string('guest_name',150); $t->string('phone',30); $t->string('email',150); $t->date('check_in'); $t->date('check_out'); $t->integer('nights');
   $t->decimal('rate',12,2); $t->decimal('total',12,2); $t->decimal('downpayment',12,2);
   $t->enum('payment_method',['Bank Transfer','Walk-in'])->default('Bank Transfer'); $t->string('payment_reference',100)->default('');
   $t->enum('status',['Pending','Reserved','Confirmed','Checked In','Checked Out','Cancelled'])->default('Pending');
   $t->boolean('policy_accepted')->default(false); $t->timestamp('created_at')->useCurrent();
   $t->foreign('user_id')->references('user_id')->on('users'); $t->foreign('room_id')->references('room_id')->on('rooms');
  });
  Schema::create('transactions', function(Blueprint $t) {
   $t->increments('transaction_id'); $t->unsignedInteger('reservation_id'); $t->decimal('amount',12,2);
   $t->enum('method',['Bank Transfer','Walk-in'])->default('Bank Transfer'); $t->string('reference_details',150)->default('');
   $t->enum('status',['Pending','Verified','Rejected'])->default('Pending'); $t->timestamp('created_at')->useCurrent();
   $t->foreign('reservation_id')->references('reservation_id')->on('reservations');
  });
 }
 public function down(): void { Schema::dropIfExists('transactions'); Schema::dropIfExists('reservations'); Schema::dropIfExists('rooms'); Schema::dropIfExists('users'); }
};
