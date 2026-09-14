<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = Schema::getConnection();

        Schema::create('confirmations', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->engine = 'InnoDB';

            $table->integer('said');
            $table->text('note')->nullable();
            $table->text('in_use')->nullable();
            $table->text('racetrack');
            $table->text('metadata');
            $table->integer('isReady')->default(0);
            $table->text('data');
            $table->unsignedInteger('id', autoIncrement: true);

            $table->unique('id', 'id_UNIQUE');
            $table->index('id', 'id');
            $table->index('said', 'idx_confirmations_said');
            $table->index(['id', 'said'], 'idx_confirmations_id_said');
        });

        Schema::create('confirmationtables', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_bin';
            $table->engine = 'InnoDB';

            $table->integer('id', autoIncrement: true);
            $table->text('name')->nullable();
            $table->text('html')->nullable();

        });

        Schema::create('confkeystore', function (Blueprint $table) use ($connection) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_bin';
            $table->engine = 'InnoDB';

            $table->string('innocdate', 64);
            $table->integer('media');
            $table->string('param', 32);
            $table->string('value', 32)->nullable();
            $table->unsignedInteger(
                'id',
                autoIncrement: ! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true),
            );

            $table->index(
                'id',
                in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)
                    ? 'id'
                    : 'confkeystore_id',
            );
        });

        if (in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)) {
            $connection->statement(
                'ALTER TABLE `confkeystore` MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('confkeystore');
        Schema::dropIfExists('confirmationtables');
        Schema::dropIfExists('confirmations');
    }
};
