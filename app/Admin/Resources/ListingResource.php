<?php

namespace App\Admin\Resources;

use App\Admin\Admin;
use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared fields for bookable listings with affiliate links.
 */
abstract class ListingResource extends Resource
{
    public static string $group = 'Listings';

    public static array $search = ['name', 'description', 'address'];

    protected function publishingFields(): array
    {
        return [
            Field::select('status', Admin::STATUSES)->required()->default('draft'),
            Field::boolean('is_active', 'Active')->default(true),
            Field::boolean('is_featured', 'Featured'),
        ];
    }

    protected function affiliateFields(): array
    {
        return [
            Field::url('affiliate_link', 'Affiliate Link')->wide()
                ->help('Write {affiliate_id} where your ID goes and it is filled in from the network, e.g. …?cid={affiliate_id}&hid=12345'),
            Field::belongsTo('affiliate_network_id', Models\AffiliateNetwork::class, 'Affiliate Network'),
        ];
    }

    protected function seoFields(): array
    {
        return [
            Field::text('meta_title', 'Meta Title')->wide(),
            Field::textarea('meta_description', 'Meta Description'),
        ];
    }

    protected function statColumns(): array
    {
        return [
            Column::make('affiliateNetwork.name', 'Network'),
            Column::make('status', 'Status', 'badge')->sortable(),
            Column::make('is_active', 'Active', 'boolean')->sortable(),
            Column::make('is_featured', 'Featured', 'boolean')->sortable(),
            Column::make('views_count', 'Views', 'number')->sortable(),
            Column::make('clicks_count', 'Clicks', 'number')->sortable(),
        ];
    }

    public function filters(): array
    {
        return [
            'status' => Admin::STATUSES,
            'affiliate_network_id' => Models\AffiliateNetwork::orderBy('name')->pluck('name', 'id')->all(),
        ];
    }

    public function saved(Model $model, bool $created): void
    {
        // Fall back to the network's commission when the listing has none
        if (in_array('commission_rate', $model->getFillable())
            && $model->commission_rate === null
            && $model->affiliateNetwork?->default_commission_rate !== null) {
            $model->update(['commission_rate' => $model->affiliateNetwork->default_commission_rate]);
        }
    }

        public function actions(): array
    {
        return [
            'publish' => ['label' => 'Publish', 'attributes' => ['status' => 'published']],
            'unpublish' => ['label' => 'Unpublish', 'attributes' => ['status' => 'draft']],
        ];
    }
}
