<?php

namespace App\Admin\Resources;

use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;
use Illuminate\Database\Eloquent\Model;

class AffiliateNetworkResource extends Resource
{
    public static string $model = Models\AffiliateNetwork::class;

    public static string $key = 'affiliate-networks';

    public static string $label = 'Affiliate Networks';

    public static string $singular = 'Affiliate Network';

    public static string $icon = 'Handshake';

    public static string $group = 'Listings';

    public static array $search = ['name', 'affiliate_id', 'notes'];

    public static string $sort = 'name';

    public static string $direction = 'asc';

    public function fields(): array
    {
        return [
            Field::text('name')->required()->placeholder('e.g. Agoda'),
            Field::slug(),
            Field::text('affiliate_id', 'Affiliate ID')->placeholder('Your partner / CID number')
                ->help('Use {affiliate_id} in a listing\'s affiliate link and it will be filled in from here.'),
            Field::select('category', Models\AffiliateNetwork::CATEGORIES)->required()->default('general'),
            Field::url('website')->placeholder('https://www.agoda.com'),
            Field::decimal('default_commission_rate', 'Default Commission (%)')->rules(['between:0,100'])
                ->help('Used for new hotels and tours on this network when they have no rate of their own.'),
            Field::boolean('is_active', 'Active')->default(true),
            Field::textarea('notes')->placeholder('Login URL, payout terms, contact…'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('name')->sortable(),
            Column::make('category', 'Category', 'badge')->sortable(),
            Column::make('affiliate_id', 'Affiliate ID'),
            Column::make('default_commission_rate', 'Commission %', 'number')->sortable(),
            Column::make('listings', 'Listings', 'number')->value(fn ($network) => $network->listingsCount()),
            Column::make('is_active', 'Active', 'boolean')->sortable(),
        ];
    }

    public function filters(): array
    {
        return ['category' => Models\AffiliateNetwork::CATEGORIES];
    }

    public function publicUrl(Model $model): ?string
    {
        return $model->website;
    }
}
