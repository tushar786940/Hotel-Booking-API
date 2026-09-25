<x-filament-panels::page>
    {{-- Summary cards use progressively wider grids without overflowing narrow screens. --}}
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-6 lg:grid-cols-4">
        <x-filament::section>
            <div class="min-w-0 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Total Revenue</p>
                <p class="break-words text-2xl font-bold text-primary-600 sm:text-3xl">
                    ${{ number_format($totalRevenue, 2) }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="min-w-0 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Monthly Revenue</p>
                <p class="break-words text-2xl font-bold text-success-600 sm:text-3xl">
                    ${{ number_format($monthlyRevenue, 2) }}
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="min-w-0 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Occupancy Rate</p>
                <p class="break-words text-2xl font-bold text-info-600 sm:text-3xl">
                    {{ $occupancyRate }}%
                </p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div class="min-w-0 text-center">
                <p class="text-sm text-gray-500 dark:text-gray-400">Avg Booking Value</p>
                <p class="break-words text-2xl font-bold text-warning-600 sm:text-3xl">
                    ${{ number_format($avgBookingValue, 2) }}
                </p>
            </div>
        </x-filament::section>
    </div>

    <x-filament::section heading="🏆 Top Performing Hotels">
        {{-- Cards are easier to scan than a horizontally scrolling table on small screens. --}}
        <div class="space-y-3 md:hidden">
            @forelse ($topHotels as $index => $hotel)
                <article class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
                    <div class="flex min-w-0 items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-primary-600">
                                No. {{ $index + 1 }}
                            </p>
                            <p class="break-words font-semibold text-gray-950 dark:text-white">
                                {{ $hotel->name }}
                            </p>
                            <p class="break-words text-sm text-gray-500 dark:text-gray-400">
                                {{ $hotel->city }}, {{ $hotel->country }}
                            </p>
                        </div>

                        <div class="shrink-0 text-right">
                            <p class="mb-1 text-xs text-gray-500 dark:text-gray-400">Bookings</p>
                            <x-filament::badge color="info">
                                {{ $hotel->bookings_count }}
                            </x-filament::badge>
                        </div>
                    </div>
                </article>
            @empty
                <p class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">
                    No hotel performance data is available yet.
                </p>
            @endforelse
        </div>

        {{-- Keep a semantic table from the medium breakpoint upward. --}}
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th scope="col" class="whitespace-nowrap py-2 pr-4 text-left">No.</th>
                        <th scope="col" class="py-2 pr-4 text-left">Hotel</th>
                        <th scope="col" class="py-2 pr-4 text-left">City</th>
                        <th scope="col" class="whitespace-nowrap py-2 text-right">Total Bookings</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($topHotels as $index => $hotel)
                        <tr class="border-b border-gray-200 last:border-b-0 dark:border-white/10">
                            <td class="whitespace-nowrap py-3 pr-4 font-bold text-primary-600">
                                {{ $index + 1 }}
                            </td>
                            <td class="py-3 pr-4 font-semibold">{{ $hotel->name }}</td>
                            <td class="py-3 pr-4 text-gray-500 dark:text-gray-400">
                                {{ $hotel->city }}, {{ $hotel->country }}
                            </td>
                            <td class="whitespace-nowrap py-3 text-right">
                                <x-filament::badge color="info">
                                    {{ $hotel->bookings_count }}
                                </x-filament::badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-gray-500 dark:text-gray-400">
                                No hotel performance data is available yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
