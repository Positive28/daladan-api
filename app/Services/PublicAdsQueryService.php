<?php

namespace App\Services;

use App\Models\Ad;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final class PublicAdsQueryService
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->query($filters)
            ->orderByLiveHighlight()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * @param  array{category_id?: int|null, subcategory_id?: int|null, search?: string|null, location?: string|null}  $filters
     */
    public function query(array $filters): Builder
    {
        $q = Ad::query()
            ->where('status', Ad::STATUS_ACTIVE)
            ->with(['category', 'subcategory', 'seller']);

        if (!empty($filters['category_id'])) {
            $q->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['subcategory_id'])) {
            $q->where('subcategory_id', $filters['subcategory_id']);
        }

        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';
        if ($search !== '') {
            $this->filterBySearchTerms($q, $this->likePattern($search));
        }

        $location = isset($filters['location']) ? trim((string) $filters['location']) : '';
        if ($location !== '') {
            $this->filterByLocation($q, $this->likePattern($location));
        }

        return $q;
    }

    private function filterBySearchTerms(Builder $query, string $pattern): void
    {
        $query->where(function (Builder $w) use ($pattern) {
            $this->whereInsensitive($w, 'title', $pattern);
            $w->orWhere(fn (Builder $ad) => $this->whereInsensitive($ad, 'description', $pattern))
                ->orWhereHas(
                    'category',
                    fn (Builder $cat) => $this->whereInsensitive($cat, 'name', $pattern)
                )
                ->orWhereHas(
                    'subcategory',
                    fn (Builder $sub) => $this->whereInsensitive($sub, 'name', $pattern)
                );
        });
    }

    private function filterByLocation(Builder $query, string $pattern): void
    {
        $query->where(function (Builder $w) use ($pattern) {
            $this->whereInsensitive($w, 'district', $pattern);

            $w->orWhereIn('region_id', function (QueryBuilder $sub) use ($pattern) {
                $sub->from('regions')->select('id');
                $this->whereInsensitiveQualified($sub, 'regions.name_uz', $pattern);
            });

            $w->orWhereIn('city_id', function (QueryBuilder $sub) use ($pattern) {
                $sub->from('cities')->select('id');
                $this->whereInsensitiveQualified($sub, 'cities.name_uz', $pattern);
            });
        });
    }

    private function whereInsensitive(Builder $query, string $column, string $pattern): void
    {
        $qualified = $query->qualifyColumn($column);
        $this->whereInsensitiveQualified($query, $qualified, $pattern);
    }

    private function whereInsensitiveQualified(Builder|QueryBuilder $query, string $qualified, string $pattern): void
    {
        if ($query->getConnection()->getDriverName() === 'pgsql') {
            $query->where($qualified, 'ilike', $pattern);

            return;
        }

        $query->whereRaw('LOWER('.$qualified.') LIKE LOWER(?)', [$pattern]);
    }

    private function likePattern(string $term): string
    {
        return '%'.$this->escapeLike($term).'%';
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
