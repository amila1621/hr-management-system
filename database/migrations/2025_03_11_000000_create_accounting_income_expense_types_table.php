<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_income_expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // 'income' or 'expense'
            $table->string('unit')->default('EUR');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Insert default values
        DB::table('accounting_income_expense_types')->insert([
            ['name' => 'Apartment Rent', 'type' => 'expense', 'unit' => 'EUR', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Apartment Deposit', 'type' => 'expense', 'unit' => 'EUR', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Salary Remuneration', 'type' => 'expense', 'unit' => 'EUR', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bonus', 'type' => 'expense', 'unit' => 'EUR', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Reimbursements', 'type' => 'expense', 'unit' => 'EUR', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Car Mileage', 'type' => 'expense', 'unit' => 'km', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Others', 'type' => 'expense', 'unit' => '', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounting_income_expense_types');
    }
};
