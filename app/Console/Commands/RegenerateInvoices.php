<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\InvoiceService;
use Illuminate\Console\Command;

class RegenerateInvoices extends Command
{
    /**
     * The name and signature of the console command.
     * {hotel?} = optional hotel ID argument
     * --month  = optional flag to filter by month
     */
    protected $signature = 'invoices:regenerate
                            {hotel? : Hotel ID to regenerate invoices for}
                            {--month= : Month in YYYY-MM format}';

    protected $description = 'Regenerate PDF invoices for bookings';

    public function __construct(
        protected InvoiceService $invoiceService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $query = Booking::whereHas('payment', function ($q) {
            $q->where('status', 'completed');
        });

        // Filter by hotel if provided
        if ($hotelId = $this->argument('hotel')) {
            $query->where('hotel_id', $hotelId);
        }

        // Filter by month if provided
        if ($month = $this->option('month')) {
            $query->whereYear('created_at', substr($month, 0, 4))
                ->whereMonth('created_at', substr($month, 5, 2));
        }

        $bookings = $query->get();

        if ($bookings->isEmpty()) {
            $this->warn('No paid bookings found matching the criteria.');
            return Command::SUCCESS;
        }

        $this->info("Found {$bookings->count()} bookings. Regenerating invoices...");

        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();

        $success = 0;
        $failed  = 0;

        foreach ($bookings as $booking) {
            try {
                $booking->load(['user', 'hotel', 'room.roomType', 'payment']);
                $this->invoiceService->save($booking);
                $success++;
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed: {$booking->booking_reference} - {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✅ Done! {$success} invoices regenerated.");
        if ($failed > 0) {
            $this->warn("⚠️  {$failed} invoices failed.");
        }

        return Command::SUCCESS;
    }
}
