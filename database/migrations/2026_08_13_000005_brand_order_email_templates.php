<?php

use App\Support\OrderEmailTemplates;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Restyle the order emails to match the storefront.
     *
     * Only rows still holding a shipped revision are replaced — an admin who has
     * already reworded the template in the panel keeps their copy.
     */
    public function up(): void
    {
        OrderEmailTemplates::syncUnmodified();
    }

    public function down(): void
    {
        // Forward-only: the plain-text originals are recoverable from the seeder.
    }
};
