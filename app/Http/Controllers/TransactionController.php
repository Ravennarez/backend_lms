<?php
namespace App\Http\Controllers;
use App\Http\Requests\BorrowBookRequest;
use App\Http\Requests\ReturnBookRequest;
use App\Models\Book;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
class TransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }
    /**
     * Get list of transactions
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
{
    $query = Transaction::with(['user', 'book']); // Eager load relationships
    
    // Admin sees all transactions, users see only their own
    if (!request()->user()->isAdmin()) {
        $query->where('user_id', request()->user()->id);
    }

    // Apply filters
    if (request()->has('status')) {
        $query->where('status', request()->status);
    }

    if (request()->has('overdue')) {
        $query->where('due_date', '<', now())
            ->where('status', 'borrowed');
   }

    // Pagination
    $perPage = request()->get('per_page', 10);
    $transactions = $query->latest()->paginate($perPage);

    return response()->json([
        'success' => true,
        'data' => $transactions->items(),
        'meta' => [
            'current_page' => $transactions->currentPage(),
            'last_page' => $transactions->lastPage(),
            'per_page' => $transactions->perPage(),
            'total' => $transactions->total(),
        ]
    ]);
}
    /**
     * Borrow a book
     *
     * @param BorrowBookRequest $request
     * @return JsonResponse
     */
    public function store(BorrowBookRequest $request): JsonResponse
    {
        $user = $request->user();
        $book = Book::findOrFail($request->book_id);
        // Check book availability
        if ($book->available_copies < 1) {
            return $this->errorResponse('No available copies of this book', 400);
        }
        // Check if user already has this book borrowed
        $existingBorrow = Transaction::where('user_id', $user->id)
            ->where('book_id', $book->id)
            ->where('status', 'borrowed')
            ->exists();
        if ($existingBorrow) {
            return $this->errorResponse('You have already borrowed this book', 400);
        }
        // Create transaction
        $transaction = Transaction::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'borrowed_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(14),
            'status' => 'borrowed',
        ]);
        // Update book availability
        $book->decrement('available_copies');
        return $this->successResponse(
            $transaction->load('book'),
            'Book borrowed successfully',
            201
        );
    }
    /**
     * Return a borrowed book
     *
     * @param ReturnBookRequest $request
     * @param Transaction $transaction
     * @return JsonResponse
     */
    public function returnBook(ReturnBookRequest $request, Transaction $transaction): JsonResponse
    {
        // Check if already returned
        if ($transaction->status === 'returned') {
            return $this->errorResponse('Book already returned', 400);
        }
        // Authorization check
        if (!$request->user()->isAdmin() && $transaction->user_id !== $request->user()->id) {
            return $this->unauthorizedResponse();
        }
        // Update transaction
        $transaction->update([
            'returned_date' => Carbon::now(),
            'status' => 'returned',
        ]);
        // Update book availability
        $transaction->book->increment('available_copies');
        return $this->successResponse(
            $transaction->load('book'),
            'Book returned successfully'
        );
    }
    /**
 * Get user transaction statistics
 *
 * @return JsonResponse
 */
public function userStats(): JsonResponse
{
    try {
        $user = request()->user();
        
        $stats = [
            'total_borrowed' => Transaction::where('user_id', $user->id)->count(),
            'currently_borrowed' => Transaction::where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->count(),
            'overdue_books' => Transaction::where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->where('due_date', '<', now())
                ->count()
        ];
        
        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to load user statistics',
            'error' => $e->getMessage()
        ], 500);
    }
}
    /**
     * Mark a book as returned
     *
     * @param Transaction $transaction
     * @return JsonResponse
     */
    public function markAsReturned(Transaction $transaction): JsonResponse
    {
        if ($transaction->status === 'returned') {
            return $this->errorResponse('Book already returned', 400);
        }

        DB::beginTransaction();
        try {
            $transaction->update([
                'status' => 'returned',
                'returned_date' => now()
            ]);

            $transaction->book->increment('available_copies');
            
            DB::commit();
            
            return $this->successResponse(
                $transaction->load('book'),
                'Book marked as returned successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to mark as returned: ' . $e->getMessage(), 500);
        }
    }
}  
