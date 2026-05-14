<?php

namespace App\Http\Controllers;

use App\Http\Requests\Budget\StoreTransactionRequest;
use App\Http\Requests\Budget\UpdateTransactionRequest;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\Money\Money;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TransactionsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Transaction::query()
            ->with(['account', 'category'])
            ->orderByDesc('posted_date')
            ->orderByDesc('id');

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->integer('account_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $term = '%'.$request->string('search').'%';
            $query->where(fn ($q) => $q
                ->where('description', 'like', $term)
                ->orWhere('merchant_name', 'like', $term)
            );
        }

        $transactions = $query->paginate(50)->withQueryString();

        return Inertia::render('transactions/index', [
            'transactions' => $transactions,
            'accounts' => $this->accountOptions(),
            'categories' => $this->categoryOptions(),
            'filters' => $request->only(['account_id', 'category_id', 'status', 'search']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('transactions/create', [
            'accounts' => $this->accountOptions(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(StoreTransactionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $cents = Money::fromDollars($data['amount'])->cents;
        $status = $data['status'];

        Transaction::create([
            'account_id' => $data['account_id'],
            'category_id' => $data['category_id'] ?? null,
            'amount_cents' => $cents,
            'currency' => 'USD',
            'posted_date' => $status === 'posted' ? $data['date'] : null,
            'pending_date' => $status === 'pending' ? $data['date'] : null,
            'description' => $data['description'],
            'source' => 'manual',
            'status' => $status,
            'notes' => $data['notes'] ?? null,
            'excluded_from_budget' => $data['excluded_from_budget'] ?? false,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction added.']);

        return to_route('transactions.index');
    }

    public function edit(Transaction $transaction): Response
    {
        return Inertia::render('transactions/edit', [
            'transaction' => [
                'id' => $transaction->id,
                'account_id' => $transaction->account_id,
                'category_id' => $transaction->category_id,
                'amount_cents' => $transaction->amount_cents,
                'date' => ($transaction->posted_date ?? $transaction->pending_date)?->toDateString(),
                'status' => $transaction->status,
                'source' => $transaction->source,
                'description' => $transaction->description,
                'notes' => $transaction->notes,
                'excluded_from_budget' => $transaction->excluded_from_budget,
            ],
            'accounts' => $this->accountOptions(),
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $data = $request->validated();
        $payload = collect($data)->except(['amount', 'date'])->all();

        if (array_key_exists('amount', $data)) {
            $payload['amount_cents'] = Money::fromDollars($data['amount'])->cents;
        }
        $status = $data['status'] ?? $transaction->status;
        if (array_key_exists('date', $data)) {
            $payload['posted_date'] = $status === 'posted' ? $data['date'] : null;
            $payload['pending_date'] = $status === 'pending' ? $data['date'] : null;
        }

        $transaction->fill($payload)->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction updated.']);

        return to_route('transactions.index');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $transaction->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Transaction deleted.']);

        return to_route('transactions.index');
    }

    private function accountOptions(): array
    {
        return Account::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name', 'kind'])
            ->toArray();
    }

    private function categoryOptions(): array
    {
        return Category::query()
            ->where('is_archived', false)
            ->orderBy('position')
            ->orderBy('name')
            ->get(['id', 'name', 'group', 'icon'])
            ->toArray();
    }
}
