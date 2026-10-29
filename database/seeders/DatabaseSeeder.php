<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class DatabaseSeeder extends Seeder {
 public function run(): void {
  if (DB::table('rooms')->count()===0) DB::table('rooms')->insert([
   ['room_number'=>'101','room_type'=>'Standard Room','image'=>'','capacity'=>2,'rate'=>23000,'status'=>'Available'],
   ['room_number'=>'201','room_type'=>'Deluxe Room','image'=>'','capacity'=>2,'rate'=>35000,'status'=>'Available'],
   ['room_number'=>'301','room_type'=>'Family Size','image'=>'','capacity'=>5,'rate'=>52000,'status'=>'Available'],
   ['room_number'=>'401','room_type'=>'Suite','image'=>'','capacity'=>4,'rate'=>86000,'status'=>'Available']
  ]);
 }
}
