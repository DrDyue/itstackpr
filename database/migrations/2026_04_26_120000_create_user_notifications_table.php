<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Izveido lietotāju paziņojumu tabulu.
     *
     * Šī tabula glabā persistētos paziņojumus, kas nav tikai "gaidošie"
     * pieteikumi, bet jau notikušu darbību rezultāti: pieteikums apstiprināts,
     * pieteikums noraidīts, remonts sākts/pabeigts vai ierīce piešķirta.
     *
     * `data` laukā glabājas strukturēta informācija paziņojuma kartītei
     * frontend pusē: ierīces nosaukums, kods, atrašanās vieta, iemesls un CTA teksts.
     */
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 80);
            $table->string('accent', 40)->default('sky');
            $table->string('title', 160);
            $table->text('message');
            $table->text('url')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Atceļ paziņojumu funkcijas datubāzes izmaiņas.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
