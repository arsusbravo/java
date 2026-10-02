<?php

namespace App\Admin\Resources;

use App\Admin\Admin;
use App\Admin\Column;
use App\Admin\Field;
use App\Admin\Resource;
use App\Models;
use Illuminate\Database\Eloquent\Model;

class ReviewResource extends Resource
{
    public static string $model = Models\Review::class;

    public static string $key = 'reviews';

    public static string $label = 'Reviews';

    public static string $singular = 'Review';

    public static string $icon = 'Star';

    public static array $search = ['user_name', 'user_email', 'comment'];

    public static array $with = ['reviewable'];

    public const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public function fields(): array
    {
        return [
            Field::morphTo('reviewable', Admin::LISTINGS, 'Reviewed Item')->required()->wide(),
            Field::text('user_name', 'Reviewer Name')->required(),
            Field::email('user_email', 'Reviewer Email')->required(),
            Field::select('rating', [5 => '★★★★★ (5)', 4 => '★★★★ (4)', 3 => '★★★ (3)', 2 => '★★ (2)', 1 => '★ (1)'])->required()->default(5),
            Field::select('status', self::STATUSES)->required()->default('pending'),
            Field::textarea('comment'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('user_name', 'Reviewer')->sortable(),
            Column::make('reviewable', 'Item')->value(fn ($review) => $review->reviewable
                ? class_basename($review->reviewable_type) . ': ' . $review->reviewable->name
                : '(deleted)'),
            Column::make('rating', 'Rating', 'number')->sortable(),
            Column::make('comment')->value(fn ($review) => str($review->comment)->limit(80)->toString()),
            Column::make('status', 'Status', 'badge')->sortable(),
            Column::make('created_at', 'Submitted', 'date')->sortable(),
        ];
    }

    public function filters(): array
    {
        return ['status' => self::STATUSES, 'rating' => [5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']];
    }

    public function actions(): array
    {
        return [
            'approve' => ['label' => 'Approve', 'attributes' => ['status' => 'approved']],
            'reject' => ['label' => 'Reject', 'attributes' => ['status' => 'rejected']],
        ];
    }

    public function title(Model $model): string
    {
        return "Review by {$model->user_name}";
    }
}
