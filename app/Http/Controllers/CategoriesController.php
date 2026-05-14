<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budget\StoreCategoryRequest;
use App\Http\Requests\Budget\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoriesController extends Controller
{
    public function index(): Response
    {
        $monthStart = CarbonImmutable::today()->startOfMonth();
        $today = CarbonImmutable::today();
        $threeMonthsAgo = $today->subMonths(3)->startOfMonth();

        $categories = Category::query()
            ->where('is_archived', false)
            ->orderBy('position')
            ->orderBy('name')
            ->get()
            ->map(function (Category $c) use ($monthStart, $today, $threeMonthsAgo) {
                $mtd = (int) Transaction::query()
                    ->where('category_id', $c->id)
                    ->where('status', 'posted')
                    ->where('excluded_from_budget', false)
                    ->whereBetween('posted_date', [$monthStart->toDateString(), $today->toDateString()])
                    ->sum('amount_cents');

                $trailing = (int) Transaction::query()
                    ->where('category_id', $c->id)
                    ->where('status', 'posted')
                    ->where('excluded_from_budget', false)
                    ->whereBetween('posted_date', [$threeMonthsAgo->toDateString(), $today->toDateString()])
                    ->sum('amount_cents');

                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                    'group' => $c->group,
                    'icon' => $c->icon,
                    'color' => $c->color,
                    'monthly_target_cents' => $c->monthly_target_cents,
                    'mtd_actual_cents' => abs($mtd),
                    'trailing_3mo_avg_cents' => (int) round(abs($trailing) / 3),
                ];
            });

        return Inertia::render('categories/index', [
            'categories' => $categories,
        ]);
    }

    public function show(Category $category): Response
    {
        $today = CarbonImmutable::today();
        $start = $today->subMonths(11)->startOfMonth();

        $rows = Transaction::query()
            ->where('category_id', $category->id)
            ->where('status', 'posted')
            ->where('excluded_from_budget', false)
            ->whereBetween('posted_date', [$start->toDateString(), $today->toDateString()])
            ->orderByDesc('posted_date')
            ->take(200)
            ->with(['account'])
            ->get()
            ->map(fn (Transaction $t) => [
                'id' => $t->id,
                'amount_cents' => $t->amount_cents,
                'description' => $t->description,
                'date' => $t->posted_date?->toDateString(),
                'account_name' => $t->account?->name,
            ]);

        $monthly = [];
        for ($i = 0; $i < 12; $i++) {
            $m = $start->addMonths($i);
            $sum = (int) Transaction::query()
                ->where('category_id', $category->id)
                ->where('status', 'posted')
                ->where('excluded_from_budget', false)
                ->whereBetween('posted_date', [$m->toDateString(), $m->endOfMonth()->toDateString()])
                ->sum('amount_cents');
            $monthly[] = [
                'month' => $m->format('Y-m'),
                'amount_cents' => abs($sum),
            ];
        }

        return Inertia::render('categories/show', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'group' => $category->group,
                'icon' => $category->icon,
                'color' => $category->color,
                'monthly_target_cents' => $category->monthly_target_cents,
            ],
            'transactions' => $rows,
            'monthly' => $monthly,
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $slug = $data['slug'] ?? Str::slug($data['name']);

        Category::create([
            'name' => $data['name'],
            'slug' => $slug,
            'parent_id' => $data['parent_id'] ?? null,
            'group' => $data['group'],
            'monthly_target_cents' => isset($data['monthly_target'])
                ? Money::fromDollars($data['monthly_target'])->cents
                : null,
            'color' => $data['color'] ?? null,
            'icon' => $data['icon'] ?? null,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category created.']);

        return to_route('categories.index');
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();
        $payload = collect($data)->except('monthly_target')->all();
        if (array_key_exists('monthly_target', $data)) {
            $payload['monthly_target_cents'] = $data['monthly_target'] !== null
                ? Money::fromDollars($data['monthly_target'])->cents
                : null;
        }

        $category->fill($payload)->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category updated.']);

        return to_route('categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->fill(['is_archived' => true])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Category archived.']);

        return to_route('categories.index');
    }
}
