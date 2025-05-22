<?php
namespace Database\Seeders;
use App\Models\Book;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Library Admin',
            'email' => 'admin@library.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password123'), // More secure password
            'role' => 'admin',
            'remember_token' => Str::random(10),
        ]);
        // Create regular users
        $users = User::factory()->count(10)->create([
            'role' => 'user',
            'password' => Hash::make('password'), // Consistent password for testing
        ]);
        // Create sample books
        $books = [
            [
                'title' => 'The Psychology of Money',
                'author' => 'Morgan Housel',
                'isbn' => '9780857197689',
                'genre' => 'Finance',
                'description' => 'Timeless lessons on wealth, greed, and happiness.',
                'total_copies' => 5,
                'available_copies' => 5,
            ],
            [
                'title' => 'Deep Work',
                'author' => 'Cal Newport',
                'isbn' => '9781455586691',
                'genre' => 'Productivity',
                'description' => 'Rules for focused success in a distracted world.',
                'total_copies' => 4,
                'available_copies' => 4,
            ],
            [
                'title' => 'Sapiens: A Brief History of Humankind',
                'author' => 'Yuval Noah Harari',
                'isbn' => '9780062316097',
                'genre' => 'History',
                'description' => 'Exploring the history of human evolution and civilization.',
                'total_copies' => 6,
                'available_copies' => 6,
            ],
            // Additional books
            [
                'title' => 'The Alchemist',
                'author' => 'Paulo Coelho',
                'isbn' => '9780062315007',
                'genre' => 'Fiction',
                'description' => 'A shepherd boy\'s journey to discover his personal legend.',
                'total_copies' => 7,
                'available_copies' => 7,
            ],
            [
                'title' => 'Atomic Habits',
                'author' => 'James Clear',
                'isbn' => '9780735211292',
                'genre' => 'Self-Help',
                'description' => 'Tiny changes, remarkable results - building good habits.',
                'total_copies' => 5,
                'available_copies' => 5,
            ]
        ];
        $createdBooks = collect();
        foreach ($books as $bookData) {
            $createdBooks->push(Book::create($bookData));
        }
        // Create sample transactions
        foreach ($users as $user) {
            // Each user borrows 1-3 random books
            $booksToBorrow = $createdBooks->random(rand(1, 3));
            
            foreach ($booksToBorrow as $book) {
                if ($book->available_copies > 0) {
                    $borrowedDate = Carbon::now()->subDays(rand(1, 30));
                    
                    Transaction::create([
                        'user_id' => $user->id,
                        'book_id' => $book->id,
                        'borrowed_date' => $borrowedDate,
                        'due_date' => $borrowedDate->copy()->addDays(14),
                        'status' => rand(0, 1) ? 'borrowed' : 'returned',
                        'returned_date' => rand(0, 1) ? $borrowedDate->copy()->addDays(rand(1, 14)) : null,
                    ]);
                    if ($book->available_copies > 0) {
                        $book->decrement('available_copies');
                    }
                }
            }
        }
    }
}