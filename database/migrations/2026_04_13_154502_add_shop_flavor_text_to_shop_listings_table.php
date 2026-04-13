<?php

use App\Models\ShopListing;
use App\Support\ShopCatalogText;
use App\Support\ShopListingTextSplitter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shop_listings', function (Blueprint $table) {
            $table->text('shop_flavor_text')->nullable()->after('shop_description');
        });

        ShopListing::query()->orderBy('id')->each(function (ShopListing $listing): void {
            $raw = $listing->shop_description ?? '';
            $split = ShopListingTextSplitter::split($raw);
            $mechanical = ShopCatalogText::normalizeMechanical($split['description']);
            $listing->shop_flavor_text = $split['flavor'];
            $listing->shop_description = $mechanical;
            $listing->save();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_listings', function (Blueprint $table) {
            $table->dropColumn('shop_flavor_text');
        });
    }
};
