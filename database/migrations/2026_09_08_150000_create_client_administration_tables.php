<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clientcategories', function (Blueprint $table) {                        
            $table->integer('id', autoIncrement: true);
            $table->text('name')->nullable();
        });

        Schema::create('clients', function (Blueprint $table) {
            
            $table->integer('id', autoIncrement: true);
            $table->string('reference', 5)->nullable();
            $table->string('name', 256)->nullable();
            $table->string('title', 12)->nullable();
            $table->string('fname', 128)->nullable();
            $table->string('mname', 128)->nullable();
            $table->string('lname', 128)->nullable();
            $table->string('street_name', 128)->nullable();
            $table->string('street_number', 6)->nullable();
            $table->string('postal_code', 12)->nullable();
            $table->string('place', 128)->nullable();
            $table->string('country', 128)->nullable();
            $table->string('telephone', 32)->nullable();
            $table->string('cellphone', 32)->nullable();
            $table->string('email', 512)->nullable();
            $table->text('notes')->nullable();
            $table->text('attachment')->nullable();
            $table->integer('active')->default(1);
            $table->integer('category')->nullable();
            $table->integer('trip_red')->default(1);
            $table->text('report_notes')->nullable();
            $table->text('nvwa_number')->nullable();
            $table->text('debit_number')->nullable();
        });

        Schema::create('categories_clients', function (Blueprint $table) {
            
            $table->integer('id', autoIncrement: true);
            $table->integer('client_id');
            $table->integer('clientcategory_id');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories_clients');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('clientcategories');
    }
};