<?php

namespace App\Models\Concerns;

use App\Models\AffiliateNetwork;

trait HasAffiliateLink
{
    public function affiliateNetwork()
    {
        return $this->belongsTo(AffiliateNetwork::class);
    }

    /**
     * The affiliate link with `{affiliate_id}` replaced by the network's ID,
     * so changing the ID on the network updates every link.
     */
    public function getAffiliateUrlAttribute(): ?string
    {
        if (! $this->affiliate_link) {
            return null;
        }

        return str_replace('{affiliate_id}', (string) $this->affiliateNetwork?->affiliate_id, $this->affiliate_link);
    }
}
